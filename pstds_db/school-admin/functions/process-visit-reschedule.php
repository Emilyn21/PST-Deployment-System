<?php
include 'auth.php';

$loggedInUserId = $user_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitId = $_POST['visit_id'];
    $visitDate = $_POST['visit_date'];
    $visitTime = $_POST['visit_time'];

    if (empty($visitId) || empty($visitDate) || empty($visitTime)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    // Retrieve the school name based on the logged-in user
    $schoolName = null;
    $schoolQuery = "SELECT tbl_school.school_name 
                    FROM tbl_school
                    INNER JOIN tbl_school_admin ON tbl_school.id = tbl_school_admin.school_id
                    WHERE tbl_school_admin.user_id = ?";
    if ($schoolStmt = $conn->prepare($schoolQuery)) {
        $schoolStmt->bind_param("i", $loggedInUserId);
        if ($schoolStmt->execute()) {
            $result = $schoolStmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $schoolName = $row['school_name'];
            }
        }
        $schoolStmt->close();
    }

    if (!$schoolName) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch the school name']);
        exit();
    }

    // Check for existing pending requests
    $checkSql = "SELECT id FROM tbl_visit_reschedule WHERE visit_id = ? AND status = 'pending'";
    if ($stmt = $conn->prepare($checkSql)) {
        $stmt->bind_param("i", $visitId);

        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'A reschedule request has already been submitted for this visit.']);
                exit();
            }

            // Insert new reschedule request
            $insertSql = "INSERT INTO tbl_visit_reschedule (visit_id, visit_date, visit_time, status, created_by)
                          VALUES (?, ?, ?, 'pending', ?)";
            if ($insertStmt = $conn->prepare($insertSql)) {
                $insertStmt->bind_param("isss", $visitId, $visitDate, $visitTime, $loggedInUserId);

                if ($insertStmt->execute()) {
                    // Format the visit date and time
                    $formattedDate = date('M d, Y', strtotime($visitDate));
                    $formattedTime = date('g:i A', strtotime($visitTime));

                    // Prepare the notification message
                    $message = "A meeting reschedule request has been submitted by the school admin of $schoolName for $formattedDate at $formattedTime.";

                    // Fetch all active admins
                    $sqlGetAdmins = "SELECT id FROM tbl_user WHERE role = 'admin' AND isDeleted = 0 AND account_status = 'active'";
                    if ($adminStmt = $conn->prepare($sqlGetAdmins)) {
                        $adminStmt->execute();
                        $result = $adminStmt->get_result();

                        while ($admin = $result->fetch_assoc()) {
                            $adminId = $admin['id'];
                            $insertNotificationQuery = "INSERT INTO tbl_notification (user_id, message, link, type)
                                                        VALUES (?, ?, 'manage-visit.php', 'alert')";
                            if ($notifStmt = $conn->prepare($insertNotificationQuery)) {
                                $notifStmt->bind_param("is", $adminId, $message);
                                $notifStmt->execute();
                                $notifStmt->close();
                            }
                        }

                        $adminStmt->close();
                    }

                    echo json_encode(['status' => 'success', 'message' => 'Reschedule request submitted successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to submit the request']);
                }

                $insertStmt->close();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: Could not prepare insert statement']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: Could not execute check query']);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: Could not prepare check query']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

$conn->close();
?>
