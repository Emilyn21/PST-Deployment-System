<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placement_id = $_POST['placement_id'];
    $adviser_id = $_POST['adviser_id'];
    $assigned_by = $_SESSION['user_id'];

    if (!empty($placement_id) && !empty($adviser_id) && !empty($assigned_by) && is_numeric($placement_id) && is_numeric($adviser_id) && is_numeric($assigned_by)) {
        try {
            $conn->begin_transaction();

            // Check if an existing assignment exists for the given placement_id
            $checkQuery = "SELECT COUNT(*) AS count FROM tbl_adviser_assignment WHERE placement_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param('i', $placement_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $checkRow = $checkResult->fetch_assoc();
            $isUpdate = $checkRow['count'] > 0; // True if the placement_id exists
            $checkStmt->close();

            // Insert or update adviser assignment
            $query = "INSERT INTO tbl_adviser_assignment (placement_id, adviser_id, assigned_by, date_assigned) 
                      VALUES (?, ?, ?, NOW())
                      ON DUPLICATE KEY UPDATE adviser_id = VALUES(adviser_id), assigned_by = VALUES(assigned_by), date_assigned = NOW()";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request. Please try again later.']);
                exit();
            }

            $stmt->bind_param('iii', $placement_id, $adviser_id, $assigned_by);

            // Execute the statement
            if ($stmt->execute()) {
                $stmt->close();

                // Check if the placement status is approved
                $statusQuery = "
                    SELECT pl.status, p.user_id 
                    FROM tbl_pre_service_teacher AS p
                    JOIN tbl_placement AS pl ON pl.pre_service_teacher_id = p.id
                    WHERE pl.id = ?";
                $statusStmt = $conn->prepare($statusQuery);
                $statusStmt->bind_param('i', $placement_id);
                $statusStmt->execute();
                $statusResult = $statusStmt->get_result();

                if ($statusResult->num_rows > 0) {
                    $statusRow = $statusResult->fetch_assoc();
                    $placementStatus = $statusRow['status'];
                    $userId = $statusRow['user_id'];

                    // Only send a notification if the status is 'approved'
                    if ($placementStatus === 'approved') {
                        // Determine notification message for the pre-service teacher
                        if ($isUpdate) {
                            $notificationMessage = "Your adviser has been updated. Please check your placement details.";
                        } else {
                            $notificationMessage = "You have been assigned to an adviser. Please check your placement details.";
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
                }
                $statusStmt->close();

                // Fetch the user ID of the adviser
                $adviserQuery = "SELECT user_id FROM tbl_adviser WHERE id = ?";
                $adviserStmt = $conn->prepare($adviserQuery);
                $adviserStmt->bind_param('i', $adviser_id);
                $adviserStmt->execute();
                $adviserResult = $adviserStmt->get_result();

                if ($adviserResult->num_rows > 0) {
                    $adviserRow = $adviserResult->fetch_assoc();
                    $adviserUserId = $adviserRow['user_id'];

                    // Notification message for the adviser
                    $adviserNotificationMessage = "A student has been assigned to you. Please check the details.";
                    $adviserLink = "assigned-pre-service-teacher.php";

                    // Insert notification for the adviser
                    $adviserNotificationQuery = "
                        INSERT INTO tbl_notification (user_id, message, link, type) 
                        VALUES (?, ?, ?, 'info')";
                    $adviserNotificationStmt = $conn->prepare($adviserNotificationQuery);
                    $adviserNotificationStmt->bind_param('iss', $adviserUserId, $adviserNotificationMessage, $adviserLink);
                    $adviserNotificationStmt->execute();
                    $adviserNotificationStmt->close();
                }

                $adviserStmt->close();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Adviser assignment processed successfully, and notifications sent!']);
            } else {
                // Handle failure in adviser assignment
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
