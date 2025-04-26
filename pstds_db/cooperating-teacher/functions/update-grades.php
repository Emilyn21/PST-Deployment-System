<?php
include 'auth.php';

$response = ['success' => true];

if (isset($_POST['placementID']) && $_POST['placementID'] !== '') {
    $placementID = $_POST['placementID'];
    $internshipGrade = isset($_POST['internshipGrade']) && trim($_POST['internshipGrade']) !== '' 
        ? (is_numeric($_POST['internshipGrade']) ? (float)$_POST['internshipGrade'] : null) 
        : null;
    $internshipGradeSuccess = false;

    try {
        // Fetch user_id from tbl_pre_service_teacher based on placementID
        $userQuery = "
            SELECT tu.id AS user_id
            FROM tbl_user tu
            JOIN tbl_pre_service_teacher tpst ON tu.id = tpst.user_id
            JOIN tbl_placement tpl ON tpl.pre_service_teacher_id = tpst.id
            WHERE tpl.id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $placementID);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        $userStmt->close();

        if (!$user) {
            throw new Exception("No user found for the specified placement ID.");
        }
        $user_id = $user['user_id'];

        // Fetch active percentage_id from tbl_evaluation_criteria_percentage
        $percentageQuery = "
            SELECT id 
            FROM tbl_evaluation_criteria_percentage 
            WHERE isActive = 1 AND isDeleted = 0 
            LIMIT 1";
        $percentageResult = $conn->query($percentageQuery);
        $percentageRow = $percentageResult->fetch_assoc();

        if (!$percentageRow) {
            throw new Exception("No active evaluation criteria percentage found.");
        }
        $percentage_id = $percentageRow['id'];

        // Check if the grade is already the same in the database
        $internshipGradeQuery = "SELECT internship_grade, percentage_id FROM tbl_evaluation WHERE placement_id = ?";
        $gradeStmt = $conn->prepare($internshipGradeQuery);
        $gradeStmt->bind_param("i", $placementID);
        $gradeStmt->execute();
        $gradeStmt->bind_result($currentInternshipGrade, $currentPercentageId);
        $gradeExists = $gradeStmt->fetch();
        $gradeStmt->close();

        // Validate internship grade
        if ($internshipGrade !== null && is_numeric($internshipGrade)) {
            if ($internshipGrade < 0 || $internshipGrade > 100) {
                throw new Exception("The internship grade must be between 0 and 100.");
            }

            if (!$gradeExists) {
                // INSERT the internship grade and percentage_id if it doesn't exist
                $insertQuery = "
                    INSERT INTO tbl_evaluation (placement_id, internship_grade, percentage_id) 
                    VALUES (?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param('idi', $placementID, $internshipGrade, $percentage_id);

                if ($insertStmt->execute()) {
                    $response['message'] = "Internship grade inserted successfully.";
                    $internshipGradeSuccess = true;
                } else {
                    throw new Exception("Failed to insert internship grade: " . $insertStmt->error);
                }
                $insertStmt->close();
            } else if ($currentInternshipGrade != $internshipGrade || $currentPercentageId != $percentage_id) {
                // UPDATE the internship grade and percentage_id if they exist and differ
                $updateQuery = "
                    UPDATE tbl_evaluation 
                    SET internship_grade = ?, percentage_id = ? 
                    WHERE placement_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param('dii', $internshipGrade, $percentage_id, $placementID);

                if ($updateStmt->execute()) {
                    $response['message'] = "Internship grade and percentage ID updated successfully.";
                    $internshipGradeSuccess = true;
                } else {
                    throw new Exception("Failed to update internship grade: " . $updateStmt->error);
                }
                $updateStmt->close();
            } else {
                $response['message'] = "Internship grade and percentage ID are already up-to-date.";
            }
        } elseif ($currentInternshipGrade !== null && $internshipGrade === null) {
            // Handle case for setting the grade to NULL
            $updateNullQuery = "
                UPDATE tbl_evaluation 
                SET internship_grade = NULL, percentage_id = ? 
                WHERE placement_id = ?";
            $updateNullStmt = $conn->prepare($updateNullQuery);
            $updateNullStmt->bind_param('ii', $percentage_id, $placementID);

            if ($updateNullStmt->execute()) {
                $response['message'] = "Internship grade updated successfully.";
                $internshipGradeSuccess = true;
            } else {
                throw new Exception("Failed to update internship grade to NULL: " . $updateNullStmt->error);
            }
            $updateNullStmt->close();
        } else {
            $response['message'] = "No update needed.";
        }

        // Add notification
        function addNotification($conn, $user_id, $message) {
            $notifQuery = "
                INSERT INTO tbl_notification (user_id, message, type, link, is_read) 
                VALUES (?, ?, 'info', 'evaluation.php', 0)";
            $notifStmt = $conn->prepare($notifQuery);
            $notifStmt->bind_param("is", $user_id, $message);
            $notifStmt->execute();
            $notifStmt->close();
        }

        // Determine notification message and add it
        if ($internshipGradeSuccess) {
            if ($currentInternshipGrade === null || $currentInternshipGrade == 0) {
                $message = "Your internship performance has been graded.";
            } else {
                $message = "Your internship grade has been updated.";
            }
            addNotification($conn, $user_id, $message);
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
} else {
    header('Location: ../../cooperating-teacher/index.php');
    exit();
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
