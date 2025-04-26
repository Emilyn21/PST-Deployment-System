<?php
include 'auth.php';

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);
$placement_id = $data['placement_id'] ?? null;
$action = $data['action'] ?? null;

// Check for valid input
if (!$placement_id || !in_array($action, ['approve', 'decline'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    header('Location: ../../school-admin/index.php');
    exit();
}

// Determine the new status based on action
$status = $action === 'approve' ? 'approved' : 'rejected';
$placement_status = $action === 'approve' ? 'placed' : 'unplaced';
$message = $action === 'approve' ? 'Your placement has been approved.' : 'Your placement has been declined.';

// Start a transaction
$conn->begin_transaction();

try {
    // Update tbl_placement status
    $update_placement_query = "UPDATE tbl_placement SET status = ?, approved_by = ?, date_approved = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_placement_query);
    $stmt->bind_param('sii', $status, $user_id, $placement_id);
    $stmt->execute();

    // Update tbl_pre_service_teacher placement_status
    $update_teacher_query = "UPDATE tbl_pre_service_teacher SET placement_status = ? WHERE id = (SELECT pre_service_teacher_id FROM tbl_placement WHERE id = ?)";
    $stmt = $conn->prepare($update_teacher_query);
    $stmt->bind_param('si', $placement_status, $placement_id);
    $stmt->execute();

    // Get the user_id, school_name, and pre-service teacher's full name
    $get_details_query = "
        SELECT 
            pst.user_id, 
            u.first_name, 
            u.last_name, 
            s.school_name 
        FROM 
            tbl_pre_service_teacher pst 
        JOIN 
            tbl_user u ON u.id = pst.user_id
        JOIN 
            tbl_placement p ON pst.id = p.pre_service_teacher_id 
        JOIN 
            tbl_school s ON p.school_id = s.id 
        WHERE 
            p.id = ?";
    $stmt = $conn->prepare($get_details_query);
    $stmt->bind_param('i', $placement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();

    $user_id_to_notify = $details['user_id'];
    $school_name = $details['school_name'];
    $student_name = $details['first_name'] . ' ' . $details['last_name'];

    // Prepare the notification message for the student
    $message = $action === 'approve' 
        ? "Your placement at $school_name has been approved." 
        : "Your placement at $school_name has been declined.";

    $link = 'account.php';

    // Insert notification into tbl_notification for the student
    $insert_notification_query = "INSERT INTO tbl_notification (user_id, message, link) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_notification_query);
    $stmt->bind_param('iss', $user_id_to_notify, $message, $link);
    $stmt->execute();

    // If approved, notify the admin
    if ($action === 'approve') {
        // Check if the placement_id exists in tbl_adviser_assignment
        $check_assignment_query = "SELECT COUNT(*) AS count FROM tbl_adviser_assignment WHERE placement_id = ?";
        $stmt = $conn->prepare($check_assignment_query);
        $stmt->bind_param('i', $placement_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignment_exists = $result->fetch_assoc()['count'] > 0;

        // Determine admin notification message and link
        if ($assignment_exists) {
            $admin_message = "The placement of $student_name at $school_name has been approved. Check adviser assignment here.";
            $admin_link = 'assign-adviser.php';
        } else {
            $admin_message = "The placement of $student_name at $school_name has been approved. Assign an adviser now.";
            $admin_link = 'assign-adviser.php';
        }

        // Get all admin user IDs
        $get_admin_ids_query = "SELECT id FROM tbl_user WHERE role = 'admin' AND isDeleted = 0 AND account_status = 'active'";
        $result = $conn->query($get_admin_ids_query);

        // Insert notification for each admin
        $insert_admin_notification_query = "INSERT INTO tbl_notification (user_id, message, link) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_admin_notification_query);

        while ($row = $result->fetch_assoc()) {
            $admin_id = $row['id'];
            $stmt->bind_param('iss', $admin_id, $admin_message, $admin_link);
            $stmt->execute();
        }

    }

    // Commit the transaction
    $conn->commit();

    // Send success response
    echo json_encode(['success' => true, 'message' => 'Placement status updated.']);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
