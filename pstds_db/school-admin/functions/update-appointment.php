<?php
include 'auth.php';

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);
$appointmentId = $data['appointment_id'] ?? null;
$action = $data['action'] ?? null;

// Check for valid input
if (!$appointmentId || !in_array($action, ['confirm', 'deny'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    header('Location: ../../cooperating-teacher/index.php');
    exit();
}

// Start a transaction
$conn->begin_transaction();

// Check if the appointment exists
$sqlCheckAppointment = "SELECT school_id FROM tbl_appointment WHERE id = ?";
$stmt = $conn->prepare($sqlCheckAppointment);
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit();
}

// Ensure the school admin can only update appointments for their school
$sqlCheckAdminSchool = "SELECT school_id FROM tbl_school_admin WHERE user_id = ?";
$stmt = $conn->prepare($sqlCheckAdminSchool);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin || $appointment['school_id'] != $admin['school_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Prepare the update action based on the action type
$updateQuery = ($action === 'confirm') 
    ? "UPDATE tbl_appointment SET status = 'confirmed', confirmed_by = ?, confirmed_at = NOW() WHERE id = ?"
    : "UPDATE tbl_appointment SET status = 'denied', confirmed_by = ?, confirmed_at = NOW() WHERE id = ?";

$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("ii", $user_id, $appointmentId);

if ($stmt->execute()) {
    // Get the school name and appointment date/time for the notification
    $sqlGetDetails = "SELECT s.school_name, a.appointment_date, a.appointment_time FROM tbl_appointment a JOIN tbl_school s ON a.school_id = s.id WHERE a.id = ?";
    $stmt = $conn->prepare($sqlGetDetails);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $school_name = $details['school_name'];
    $appointment_date = new DateTime($details['appointment_date']);
    $appointment_time = new DateTime($details['appointment_time']);

    // Format the appointment date and time
    $current_year = (new DateTime())->format('Y');
    $appointment_year = $appointment_date->format('Y');
    $formatted_date = $appointment_date->format('M d');
    
    if ($appointment_year != $current_year) {
        // Include the year if it's not the current year
        $formatted_date = $appointment_date->format('M d, Y');
    }

    $formatted_time = $appointment_time->format('g:i A');

    // Prepare the notification message
    $message = $action === 'confirm' 
        ? "The appointment to $school_name for $formatted_date at $formatted_time has been confirmed." 
        : "The appointment to $school_name for $formatted_date at $formatted_time has been rejected.";

    // Get all admin users for notification
    $sqlGetAdmins = "SELECT id FROM tbl_user WHERE role = 'admin' AND isDeleted = 0 AND account_status = 'active'";
    $stmt = $conn->prepare($sqlGetAdmins);
    $stmt->execute();
    $result = $stmt->get_result();

    // Loop through each admin and send the notification
    while ($admin = $result->fetch_assoc()) {
        $user_id_to_notify = $admin['id'];  // Use 'id' instead of 'user_id'

        // Insert notification into tbl_notification
        $insert_notification_query = "INSERT INTO tbl_notification (user_id, message, link, type) VALUES (?, ?, 'manage-appointment.php', 'alert')";
        $stmt = $conn->prepare($insert_notification_query);
        $stmt->bind_param('is', $user_id_to_notify, $message);
        $stmt->execute();
    }

    // Commit the transaction
    $conn->commit();

    // Send success response
    echo json_encode(['success' => true, 'message' => 'Appointment ' . $action . 'ed successfully and notifications sent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$stmt->close();
$conn->close();
?>
