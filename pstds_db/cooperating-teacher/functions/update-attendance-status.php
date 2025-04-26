<?php
include 'auth.php';

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);
$attendance_id = $data['attendance_id'] ?? null;
$action = $data['action'] ?? null;

// Check for valid input
if (!$attendance_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    header('Location: ../../school-admin/index.php');
    exit();
}

// Determine the new status based on action
$status = $action === 'approve' ? 'approved' : 'rejected';

// Start a transaction
$conn->begin_transaction();

try {
    // Update tbl_attendance status
    $update_attendance_query = "UPDATE tbl_attendance SET status = ?, approved_by = ? WHERE id = ?";
    $stmt = $conn->prepare($update_attendance_query);
    $stmt->bind_param('sii', $status, $user_id, $attendance_id);
    $stmt->execute();

    // Fetch the user_id of the pre-service teacher
    $userQuery = "
        SELECT p.user_id 
        FROM tbl_attendance AS a
        JOIN tbl_placement AS pl ON a.placement_id = pl.id
        JOIN tbl_pre_service_teacher AS p ON pl.pre_service_teacher_id = p.id
        WHERE a.id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param('i', $attendance_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows > 0) {
        $userRow = $userResult->fetch_assoc();
        $userId = $userRow['user_id'];

    // Insert notification for the pre-service teacher
    $dateQuery = "SELECT DATE(time_in) AS attendance_date FROM tbl_attendance WHERE id = ?";
    $dateStmt = $conn->prepare($dateQuery);
    $dateStmt->bind_param('i', $attendance_id);
    $dateStmt->execute();
    $dateResult = $dateStmt->get_result();
    $dateRow = $dateResult->fetch_assoc();
    $rawAttendanceDate = $dateRow['attendance_date'];

    // Format the date to "Dec 3, 2024"
    $attendanceDate = date('M j, Y', strtotime($rawAttendanceDate));

    $notificationMessage = "Your attendance for {$attendanceDate} was approved.";
    $link = "manage-journal-entry.php";

    // Insert notification into tbl_notification
    $notificationQuery = "
        INSERT INTO tbl_notification (user_id, message, link, type) 
        VALUES (?, ?, ?, 'info')";
    $notificationStmt = $conn->prepare($notificationQuery);
    $notificationStmt->bind_param('iss', $userId, $notificationMessage, $link);
    $notificationStmt->execute();
    $notificationStmt->close();
    }

    // Commit the transaction
    $conn->commit();

    // Send success response
    echo json_encode(['success' => true, 'message' => 'Attendance status updated successfully, and notification sent']);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
