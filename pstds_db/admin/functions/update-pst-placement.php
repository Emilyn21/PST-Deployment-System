<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placement_id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $school_id = isset($_POST['school']) ? intval($_POST['school']) : null;
    $start_date = isset($_POST['startDate']) ? $_POST['startDate'] : null;
    $end_date = isset($_POST['endDate']) ? $_POST['endDate'] : null;

    if ($placement_id === null || $school_id === null || $start_date === null || $end_date === null) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing.']);
        exit();
    }

    if (strtotime($start_date) >= strtotime($end_date)) {
        echo json_encode(['success' => false, 'message' => 'Error: Start date must be before the end date.']);
        exit();
    }

    // Start the transaction
    $conn->begin_transaction();

    try {
        $academicYearSql = "
            SELECT ay.start_date, ay.end_date 
            FROM tbl_placement p
            JOIN tbl_pre_service_teacher pst ON p.pre_service_teacher_id = pst.id
            JOIN tbl_user u ON pst.user_id = u.id
            JOIN tbl_academic_year ay ON pst.academic_year_id = ay.id
            WHERE p.id = ? 
            AND p.isDeleted = 0 
            AND u.isDeleted = 0";
        
        if ($academicYearStmt = $conn->prepare($academicYearSql)) {
            $academicYearStmt->bind_param("i", $placement_id);
            $academicYearStmt->execute();
            $academicYearResult = $academicYearStmt->get_result();

            if ($academicYearResult->num_rows > 0) {
                $academicYear = $academicYearResult->fetch_assoc();
                $academicYearStart = strtotime($academicYear['start_date']);
                $academicYearEnd = strtotime($academicYear['end_date']);
                $placementStart = strtotime($start_date);
                $placementEnd = strtotime($end_date);

                if ($placementStart < $academicYearStart || $placementEnd > $academicYearEnd) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error: Placement dates must be within the pre-service teacher\'s academic year (' . date('M j, Y', $academicYearStart) . ' to ' . date('M j, Y', $academicYearEnd) . ').'
                    ]);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Academic year not found for this placement.']);
                exit();
            }
            $academicYearStmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to retrieve academic year.']);
            exit();
        }

        $sql = "UPDATE tbl_placement 
                SET school_id = ?, start_date = ?, end_date = ?
                WHERE id = ? AND isDeleted = 0";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('issi', $school_id, $start_date, $end_date, $placement_id);

            if ($stmt->execute()) {

                // Proceed to get the user ID of the pre-service teacher
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

                    // Insert notification
                    $notificationQuery = "
                        INSERT INTO tbl_notification (user_id, message, link, type) 
                        VALUES (?, ?, 'account.php', 'info')";
                    $notificationStmt = $conn->prepare($notificationQuery);
                    $notificationMessage = "Placement dates have been updated.";
                    $notificationStmt->bind_param('is', $userId, $notificationMessage);
                    $notificationStmt->execute();
                    $notificationStmt->close();
                }

                $userStmt->close();
                $conn->commit();
                echo json_encode(['success' => true]);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'An error occurred while updating placement details. Please try again later.']);
            }
            $stmt->close();
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to prepare the update request. Please try again.']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.']);
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
