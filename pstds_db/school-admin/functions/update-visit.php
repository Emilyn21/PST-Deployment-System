<?php
include 'auth.php';

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);
$visitId = $data['visit_id'] ?? null;
$action = $data['action'] ?? null;

// Check for valid input
if (!$visitId || !in_array($action, ['confirm', 'deny'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    header('Location: ../../cooperating-teacher/index.php');
    exit();
}

// Start a transaction
$conn->begin_transaction();

// Check if the visit exists
$sqlCheckVisit = "SELECT school_id FROM tbl_visit WHERE id = ?";
$stmt = $conn->prepare($sqlCheckVisit);
$stmt->bind_param("i", $visitId);
$stmt->execute();
$result = $stmt->get_result();
$visit = $result->fetch_assoc();

if (!$visit) {
    echo json_encode(['success' => false, 'message' => 'Visit not found']);
    exit();
}

// Ensure the school admin can only update visits for their school
$sqlCheckAdminSchool = "SELECT school_id FROM tbl_school_admin WHERE user_id = ?";
$stmt = $conn->prepare($sqlCheckAdminSchool);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin || $visit['school_id'] != $admin['school_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Prepare the update action based on the action type
$updateQuery = ($action === 'confirm') 
    ? "UPDATE tbl_visit SET status = 'confirmed', confirmed_by = ?, confirmed_at = NOW() WHERE id = ?"
    : "UPDATE tbl_visit SET status = 'denied', confirmed_by = ?, confirmed_at = NOW() WHERE id = ?";

$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("ii", $user_id, $visitId);

if ($stmt->execute()) {
    if ($action === 'confirm') {
        // Check if there's a related entry in tbl_visit_reschedule
        $sqlCheckReschedule = "SELECT id FROM tbl_visit_reschedule WHERE visit_id = ? AND isDeleted = 0";
        $stmt = $conn->prepare($sqlCheckReschedule);
        $stmt->bind_param("i", $visitId);
        $stmt->execute();
        $result = $stmt->get_result();
        $reschedule = $result->fetch_assoc();

        // If a related reschedule exists, set isDeleted to 1 to cancel the request
        if ($reschedule) {
            $updateReschedule = "UPDATE tbl_visit_reschedule SET isDeleted = 1 WHERE id = ?";
            $stmt = $conn->prepare($updateReschedule);
            $stmt->bind_param("i", $reschedule['id']);
            $stmt->execute();
        }
    }

    // Get the school name and visit date/time for the notification
    $sqlGetDetails = "SELECT s.school_name, a.visit_date, a.visit_time FROM tbl_visit a JOIN tbl_school s ON a.school_id = s.id WHERE a.id = ?";
    $stmt = $conn->prepare($sqlGetDetails);
    $stmt->bind_param("i", $visitId);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $school_name = $details['school_name'];
    $visit_date = new DateTime($details['visit_date']);
    $visit_time = new DateTime($details['visit_time']);

    // Format the visit date and time
    $current_year = (new DateTime())->format('Y');
    $visit_year = $visit_date->format('Y');
    $formatted_date = $visit_date->format('M d');
    
    if ($visit_year != $current_year) {
        // Include the year if it's not the current year
        $formatted_date = $visit_date->format('M d, Y');
    }

    $formatted_time = $visit_time->format('g:i A');

    // Prepare the notification message
    $message = $action === 'confirm' 
        ? "The visit to $school_name for $formatted_date at $formatted_time has been confirmed." 
        : "The visit to $school_name for $formatted_date at $formatted_time has been declined.";

    // Get all admin users for notification
    $sqlGetAdmins = "SELECT id FROM tbl_user WHERE role = 'admin' AND isDeleted = 0 AND account_status = 'active'";
    $stmt = $conn->prepare($sqlGetAdmins);
    $stmt->execute();
    $result = $stmt->get_result();

    // Loop through each admin and send the notification
    while ($admin = $result->fetch_assoc()) {
        $user_id_to_notify = $admin['id'];  // Use 'id' instead of 'user_id'

        // Insert notification into tbl_notification
        $insert_notification_query = "INSERT INTO tbl_notification (user_id, message, link, type) VALUES (?, ?, 'manage-visit.php', 'alert')";
        $stmt = $conn->prepare($insert_notification_query);
        $stmt->bind_param('is', $user_id_to_notify, $message);
        $stmt->execute();
    }

    // Commit the transaction
    $conn->commit();

    // Send success response
    echo json_encode(['success' => true, 'message' => 'visit ' . $action . 'ed successfully and notifications sent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$stmt->close();
$conn->close();
?>