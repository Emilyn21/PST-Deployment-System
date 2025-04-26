<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placement_id = $_POST['placement_id'];
    $cooperating_teacher_id = $_POST['cooperating_teacher_id'];
    $assigned_by = $_SESSION['user_id'];

    if (!empty($placement_id) && !empty($cooperating_teacher_id) && !empty($assigned_by) && is_numeric($placement_id) && is_numeric($cooperating_teacher_id) && is_numeric($assigned_by)) {
        try {
            $conn->begin_transaction();

            // Check if an existing assignment exists for the given placement_id
            $checkQuery = "SELECT COUNT(*) AS count FROM tbl_cooperating_teacher_assignment WHERE placement_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param('i', $placement_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkRow = $checkResult->fetch_assoc();
            $isUpdate = $checkRow['count'] > 0; // True if the placement_id exists
            $checkStmt->close();

            $query = "INSERT INTO tbl_cooperating_teacher_assignment (placement_id, cooperating_teacher_id, assigned_by, date_assigned) 
                      VALUES (?, ?, ?, NOW())
                      ON DUPLICATE KEY UPDATE cooperating_teacher_id = VALUES(cooperating_teacher_id), assigned_by = VALUES(assigned_by), date_assigned = NOW()";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request. Please try again later.']);
                exit();
            }

            $stmt->bind_param('iii', $placement_id, $cooperating_teacher_id, $assigned_by);

            // Execute the statement
            if ($stmt->execute()) {
                $stmt->close();

                // Fetch the user ID of the pre-service teacher
                $userQuery = "
                    SELECT p.user_id 
                    FROM tbl_pre_service_teacher AS p
                    JOIN tbl_placement AS pl ON pl.pre_service_teacher_id = p.id
                    WHERE pl.id = ?";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param('i', $placement_id);
                $userStmt->execute();
                $userResult = $userStmt->get_result();

                if ($userResult->num_rows > 0) {
                    $userRow = $userResult->fetch_assoc();
                    $userId = $userRow['user_id'];

                    // Determine notification message for the pre-service teacher
                    if ($isUpdate) {
                        $notificationMessage = "Your cooperating teacher has been updated. Please check your placement details.";
                    } else {
                        $notificationMessage = "You have been assigned to a cooperating teacher. Please check your placement details.";
                    }

                    // Insert notification for the pre-service teacher
                    $notificationQuery = "
                        INSERT INTO tbl_notification (user_id, message, link, type) 
                        VALUES (?, ?, 'account.php', 'info')";
                    $notificationStmt = $conn->prepare($notificationQuery);
                    $notificationStmt->bind_param('is', $userId, $notificationMessage);
                    $notificationStmt->execute();
                    $notificationStmt->close();
                }

                $userStmt->close();

                // Fetch the user ID of the cooperating teacher
                $teacherQuery = "SELECT user_id FROM tbl_cooperating_teacher WHERE id = ?";
                $teacherStmt = $conn->prepare($teacherQuery);
                $teacherStmt->bind_param('i', $cooperating_teacher_id);
                $teacherStmt->execute();
                $teacherResult = $teacherStmt->get_result();

                if ($teacherResult->num_rows > 0) {
                    $teacherRow = $teacherResult->fetch_assoc();
                    $teacherUserId = $teacherRow['user_id'];

                    // Notification message for the cooperating teacher
                    $teacherNotificationMessage = "A student has been assigned to you. Please check the details.";
                    $teacherLink = "assigned-pre-service-teacher.php";

                    // Insert notification for the cooperating teacher
                    $teacherNotificationQuery = "
                        INSERT INTO tbl_notification (user_id, message, link, type) 
                        VALUES (?, ?, ?, 'info')";
                    $teacherNotificationStmt = $conn->prepare($teacherNotificationQuery);
                    $teacherNotificationStmt->bind_param('iss', $teacherUserId, $teacherNotificationMessage, $teacherLink);
                    $teacherNotificationStmt->execute();
                    $teacherNotificationStmt->close();
                }

                $teacherStmt->close();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Cooperating teacher assignment processed successfully, and notifications sent!']);
            } else {
                // Handle failure in cooperating teacher assignment
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request. Please try again later.']);
                $stmt->close();
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
        } finally {
            $conn->close();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
