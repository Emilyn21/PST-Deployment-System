<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON payload
    $input = json_decode(file_get_contents('php://input'), true);

    // Use the correct session variable for user ID
    $userId = $_SESSION['user_id'] ?? null;
    $visitId = $input['visit_id'] ?? null;
    $action = $input['action'] ?? null;

    // Validate the request
    if (!$visitId || !$action || !in_array($action, ['confirm', 'deny']) || !$userId) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $currentTimestamp = date('Y-m-d H:i:s');

        // Variables for notification
        $createdBy = null;
        $schoolId = null;

        // Get the `created_by` from `tbl_visit_reschedule`
        $stmt = $conn->prepare("SELECT created_by FROM tbl_visit_reschedule WHERE visit_id = ? AND status = 'pending'");
        $stmt->bind_param('i', $visitId);
        $stmt->execute();
        $stmt->bind_result($createdBy);
        $stmt->fetch();
        $stmt->close();

        if (!$createdBy) {
            throw new Exception("Failed to fetch the creator of the reschedule request.");
        }

        // Get the `school_id` from `tbl_school_admin`
        $stmt = $conn->prepare("
            SELECT school_id 
            FROM tbl_school_admin 
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $createdBy);
        $stmt->execute();
        $stmt->bind_result($schoolId);
        $stmt->fetch();
        $stmt->close();

        if (!$schoolId) {
            throw new Exception("Failed to fetch the school ID for the creator.");
        }

        // Update the status of the reschedule request
        if ($action === 'confirm') {
            $stmt = $conn->prepare("
                UPDATE tbl_visit_reschedule r
                JOIN tbl_visit v ON r.visit_id = v.id
                SET 
                    r.status = 'confirmed',
                    r.confirmed_by = ?, 
                    r.confirmed_at = ?,
                    v.visit_date = r.visit_date,
                    v.visit_time = r.visit_time
                WHERE r.visit_id = ? AND r.status = 'pending';
            ");
            $stmt->bind_param('isi', $userId, $currentTimestamp, $visitId);
            $stmt->execute();
        } elseif ($action === 'deny') {
            $stmt = $conn->prepare("
                UPDATE tbl_visit_reschedule 
                SET 
                    status = 'denied',
                    confirmed_by = ?, 
                    confirmed_at = ?
                WHERE visit_id = ? AND status = 'pending';
            ");
            $stmt->bind_param('isi', $userId, $currentTimestamp, $visitId);
            $stmt->execute();
        }
        // Fetch the visit date and time from tbl_visit_reschedule
        $stmt = $conn->prepare("
            SELECT visit_date, visit_time 
            FROM tbl_visit_reschedule 
            WHERE visit_id = ? AND status IN ('confirmed', 'denied')
        ");
        $stmt->bind_param('i', $visitId);
        $stmt->execute();
        $stmt->bind_result($visitDate, $visitTime);
        $stmt->fetch();
        $stmt->close();

        // Format the date and time for the notification
        $formattedDate = date('F j, Y', strtotime($visitDate)); // Example: December 15, 2024
        $formattedTime = date('g:i A', strtotime($visitTime)); // Example: 2:30 PM

        // Prepare the notification message
        $message = ($action === 'confirm')
            ? "Your request to reschedule the visit to $formattedDate at $formattedTime has been approved. Please confirm the visit."
            : "Your request to reschedule the visit to $formattedDate at $formattedTime has been denied.";


        // Fetch active school admins under the same school
        $stmt = $conn->prepare("
            SELECT sa.user_id 
            FROM tbl_school_admin sa
            JOIN tbl_user u ON sa.user_id = u.id
            WHERE sa.school_id = ? AND u.account_status = 'active'
        ");
        $stmt->bind_param('i', $schoolId);
        $stmt->execute();
        $result = $stmt->get_result();

        // Insert notifications
        $notificationQuery = "
            INSERT INTO tbl_notification (user_id, message, link, type) 
            VALUES (?, ?, 'manage-visit.php', 'alert')
        ";
        $notifStmt = $conn->prepare($notificationQuery);

        while ($admin = $result->fetch_assoc()) {
            $notifStmt->bind_param('is', $admin['user_id'], $message);
            $notifStmt->execute();
        }

        $stmt->close();
        $notifStmt->close();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Request updated successfully and notifications sent.']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to update the request']);
    }
}
?>
