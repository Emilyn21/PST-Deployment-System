<?php
include 'auth.php';

header('Content-Type: application/json');

// Function to log errors to the server
function log_error($message) {
    error_log("[ERROR] " . $message);
}

try {
    $pstID = $_POST['pstID'] ?? null;
    $placementID = $_POST['placementID'] ?? null;
    $ePortfolioGrade = isset($_POST['ePortfolioGrade']) && trim($_POST['ePortfolioGrade']) !== '' 
        ? (is_numeric($_POST['ePortfolioGrade']) ? (float)$_POST['ePortfolioGrade'] : null) 
        : null;
    $finalDemoAverage = $_POST['finalDemoAverage'] ?? null;
    $overallAverage = $_POST['overallAverage'] ?? null;
    $observerGrades = $_POST['observerGrades'] ?? [];
    $criteriaID = $_POST['criteriaID'] ?? null;
    $observerAttachments = $_FILES['observerAttachments'] ?? null;

    $response = ['success' => true];
    $ePortfolioUpdateSuccess = false;
    $observerGradesSuccess = false;
    $evaluationID = null;

    $checkQuery = "SELECT COUNT(*) FROM tbl_eportfolio WHERE pre_service_teacher_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $pstID);
    if (!$checkStmt->execute()) {
        log_error("Failed to execute check query: " . $checkStmt->error);
        throw new Exception("Failed to check ePortfolio entry.");
    }
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();


    // Fetch user_id from tbl_pre_service_teacher based on pstID
    $userQuery = "SELECT user_id FROM tbl_pre_service_teacher WHERE id = ?";
    $userStmt = $conn->prepare($userQuery);
    if (!$userStmt) {
        log_error("Failed to prepare statement for fetching user_id: " . $conn->error);
        throw new Exception("Failed to prepare query for fetching user_id.");
    }
    $userStmt->bind_param("i", $pstID);
    if (!$userStmt->execute()) {
        log_error("Failed to execute query to fetch user_id: " . $userStmt->error);
        throw new Exception("Failed to execute query for fetching user_id.");
    }
    $userStmt->bind_result($user_id);
    if (!$userStmt->fetch()) {
        log_error("No user_id found for pre_service_teacher ID: " . $pstID);
        throw new Exception("No user found for the specified pre-service teacher ID.");
    }
    $userStmt->close();

    // Check if the grade is already the same in the database
    $eportfolioGradeQuery = "SELECT grade FROM tbl_eportfolio WHERE pre_service_teacher_id = ?";
    $eportfolioGradeStmt = $conn->prepare($eportfolioGradeQuery);
    if (!$eportfolioGradeStmt) {
        log_error("Failed to prepare statement for checking ePortfolio grade: " . $conn->error);
        throw new Exception("Failed to prepare grade check query.");
    }
    $eportfolioGradeStmt->bind_param("i", $pstID);
    if (!$eportfolioGradeStmt->execute()) {
        log_error("Failed to execute grade check query: " . $eportfolioGradeStmt->error);
        throw new Exception("Failed to execute grade check query.");
    }
    $eportfolioGradeStmt->bind_result($currentEPortfolioGrade);
    $eportfolioGradeStmt->fetch();
    $eportfolioGradeStmt->close();

    if ($ePortfolioGrade !== null && is_numeric($ePortfolioGrade)) {

        if ($ePortfolioGrade < 0 || $ePortfolioGrade > 100) {
            log_error("Invalid ePortfolio grade: " . $ePortfolioGrade);
            throw new Exception("The ePortfolio grade must be between 0 and 100.");
        }

        // Update only if the submitted grade is different
        if ($currentEPortfolioGrade != $ePortfolioGrade) {
            $stmt = $conn->prepare("UPDATE tbl_eportfolio SET grade = ? WHERE pre_service_teacher_id = ?");
            $stmt->bind_param("di", $ePortfolioGrade, $pstID);
            if ($stmt->execute()) {
                $response['ePortfolioUpdate'] = "ePortfolio grade updated successfully. $currentEPortfolioGrade $ePortfolioGrade";
                $ePortfolioUpdateSuccess = true;
            } else {
                log_error("Failed to update ePortfolio grade: " . $stmt->error);
                $response['success'] = false;
            }
            $stmt->close();

            // Update tbl_evaluation
            $query2 = "INSERT INTO tbl_evaluation (placement_id, eportfolio_grade, percentage_id) 
                       VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE eportfolio_grade = VALUES(eportfolio_grade), percentage_id = VALUES(percentage_id)";
            if ($stmt2 = $conn->prepare($query2)) {
                $stmt2->bind_param('idi', $placementID, $ePortfolioGrade, $criteriaID);
                if ($stmt2->execute()) {
                    $response['evaluationUpdate'] = "Evaluation updated successfully.";
                } else {
                    log_error("Failed to update evaluation: " . $stmt2->error);
                    $response['success'] = false;
                    $response['evaluationUpdate'] = "Failed to update evaluation.";
                }
                $stmt2->close();
            } else {
                log_error("Failed to prepare statement for tbl_evaluation: " . $conn->error);
                $response['success'] = false;
                $response['queryPreparationError'] = "Failed to prepare statement for tbl_evaluation.";
            }
        } else {
            $response['ePortfolioUpdate'] = "No update needed. Grade is already the same.";
            $ePortfolioUpdateSuccess = false;
        }
    } elseif ($currentEPortfolioGrade !== null && ($ePortfolioGrade === null || $ePortfolioGrade === "")) {
        // Handle case for null or invalid grades
        $stmt = $conn->prepare("UPDATE tbl_eportfolio SET grade = NULL WHERE pre_service_teacher_id = ?");
        $stmt->bind_param("i", $pstID);
        if ($stmt->execute()) {
            $response['ePortfolioUpdate'] = "ePortfolio grade updated successfully.";
            $ePortfolioUpdateSuccess = true;
        } else {
            log_error("Failed to update ePortfolio grade: " . $stmt->error);
            $response['success'] = false;
        }
        $stmt->close();

        $query2 = "UPDATE tbl_evaluation SET eportfolio_grade = NULL WHERE placement_id = ?";
        if ($stmt2 = $conn->prepare($query2)) {
            $stmt2->bind_param('i', $placementID);
            if ($stmt2->execute()) {
                $response['evaluationUpdate'] = "Evaluation updated successfully.";
            } else {
                log_error("Failed to update evaluation: " . $stmt2->error);
                $response['success'] = false;
                $response['evaluationUpdate'] = "Failed to update evaluation.";
            }
            $stmt2->close();
        } else {
            log_error("Failed to prepare statement for tbl_evaluation: " . $conn->error);
            $response['success'] = false;
            $response['queryPreparationError'] = "Failed to prepare statement for tbl_evaluation.";
        }
    } else {
        $ePortfolioUpdateSuccess = false;
    }

    $observerGradesUpdates = false;

    $currentFinalDemoAverageQuery = "SELECT final_demo_average FROM tbl_evaluation WHERE placement_id = ?";
    $currentFinalDemoAverageStmt = $conn->prepare($currentFinalDemoAverageQuery);
    $currentFinalDemoAverageStmt->bind_param("i", $placementID);
    if (!$currentFinalDemoAverageStmt->execute()) {
        log_error("Failed to fetch current final demo average: " . $currentFinalDemoAverageStmt->error);
        throw new Exception("Failed to fetch final demo average.");
    }
    $currentFinalDemoAverageStmt->bind_result($currentFinalDemoAverage);
    $currentFinalDemoAverageStmt->fetch();
    $currentFinalDemoAverageStmt->close();


    $validObserverGrades = array_filter($observerGrades, fn($grade) => is_numeric($grade) && $grade >= 0 && $grade <= 100);
    $totalGrades = count($validObserverGrades);
    $sumOfGrades = array_sum($validObserverGrades);
    $finalDemoAverage = $totalGrades > 0 ? $sumOfGrades / $totalGrades : $currentFinalDemoAverage;

    if ($ePortfolioUpdateSuccess || $totalGrades > 0) {
        $query = "INSERT INTO tbl_evaluation (placement_id, final_demo_average, overall_average, percentage_id) 
                  VALUES (?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                      final_demo_average = VALUES(final_demo_average),
                      overall_average = VALUES(overall_average),
                      percentage_id = VALUES(percentage_id)";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('iddi', $placementID, $finalDemoAverage, $overallAverage, $criteriaID);

            if ($stmt->execute()) {
                $evaluationID = ($stmt->insert_id) ? $stmt->insert_id : $placementID;
                
                if (!$stmt->insert_id) {
                    $evaluationQuery = "SELECT id FROM tbl_evaluation WHERE placement_id = ?";
                    $evalStmt = $conn->prepare($evaluationQuery);
                    $evalStmt->bind_param("i", $placementID);
                    if (!$evalStmt->execute()) {
                        log_error("Failed to fetch evaluation ID: " . $evalStmt->error);
                        throw new Exception("Failed to fetch evaluation ID.");
                    }
                    $evalStmt->bind_result($evaluationID);
                    $evalStmt->fetch();
                    $evalStmt->close();
                }

                $response['evaluationUpdate'] = "Evaluation updated successfully.";
                $observerGradesUpdates = true;

            } else {
                log_error("Failed to update evaluation: " . $stmt->error);
                $response['success'] = false;
                $response['evaluationUpdate'] = "Failed to update evaluation: " . $stmt->error;
            }

            $stmt->close();
        } else {
            log_error("Failed to prepare statement for evaluation update: " . $conn->error);
            $response['success'] = false;
            $response['queryPreparationError'] = "Failed to prepare statement: " . $conn->error;
        }
    } else {
        $response['evaluationUpdate'] = "No observer grades provided.";
    }

    $existingObserverNumbers = [];
    if ($evaluationID) {
        $existingGradesQuery = "SELECT observer_number FROM tbl_observer_grades WHERE evaluation_id = ?";
        $existingGradesStmt = $conn->prepare($existingGradesQuery);
        $existingGradesStmt->bind_param("i", $evaluationID);
        if (!$existingGradesStmt->execute()) {
            log_error("Failed to fetch existing observer grades: " . $existingGradesStmt->error);
            throw new Exception("Failed to fetch existing observer grades.");
        }
        $result = $existingGradesStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingObserverNumbers[$row['observer_number']] = true;
        }
        $existingGradesStmt->close();
    }

    try {
        $updatedObserverNumbers = [];
        foreach ($observerGrades as $index => $grade) {
            if (!is_numeric($grade)) {
                continue; // Skip invalid grades
            }

            $observerNumber = $index + 1;
            $attachmentLink = null;

            // Handle file upload if applicable
            if (isset($observerAttachments['name'][$index]) && !empty($observerAttachments['name'][$index])) {
                $targetDir = '../../uploads/og/';
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                $fileName = basename($observerAttachments['name'][$index]);
                $targetFilePath = $targetDir . uniqid() . "_" . $fileName;

                if (move_uploaded_file($observerAttachments['tmp_name'][$index], $targetFilePath)) {
                    $attachmentLink = $targetFilePath;
                } else {
                    $response['success'] = false;
                    $response['message'] = "Error uploading file for observer $observerNumber.";
                    continue;
                }
            }

            if (isset($existingObserverNumbers[$observerNumber])) {
                $updateObserverQuery = "
                    UPDATE tbl_observer_grades 
                    SET grade = ?, attachment_link = COALESCE(?, attachment_link), isActive = 0 
                    WHERE evaluation_id = ? AND observer_number = ?
                ";

                $updateObserverStmt = $conn->prepare($updateObserverQuery);

                $attachmentLinkParam = ($attachmentLink === NULL || $attachmentLink === '') ? NULL : $attachmentLink;

                $updateObserverStmt->bind_param("dsii", $grade, $attachmentLinkParam, $evaluationID, $observerNumber);

                if ($updateObserverStmt->execute()) {
                    $response['observerUpdate'] = "Observer grades updated successfully.";
                    $observerGradesSuccess = true;
                    $updatedObserverNumbers[$observerNumber] = true;
                } else {
                    $response['success'] = false;
                    $response['observerInsertError'][] = "Failed to update grade for observer $observerNumber: " . $updateObserverStmt->error;
                }
                $updateObserverStmt->close();
            } else {
                $insertObserverQuery = "
                    INSERT INTO tbl_observer_grades (evaluation_id, observer_number, grade, attachment_link, isActive) 
                    VALUES (?, ?, ?, ?, 0)
                ";

                $insertObserverStmt = $conn->prepare($insertObserverQuery);

                $attachmentLinkParam = ($attachmentLink === NULL || $attachmentLink === '') ? NULL : $attachmentLink;

                $insertObserverStmt->bind_param("iids", $evaluationID, $observerNumber, $grade, $attachmentLinkParam);

                if ($insertObserverStmt->execute()) {
                    $response['observerUpdate'] = "Observer grades updated successfully.";
                    $observerGradesSuccess = true;
                    $updatedObserverNumbers[$observerNumber] = true;
                } else {
                    $response['success'] = false;
                    $response['observerInsertError'][] = "Failed to insert grade for observer $observerNumber: " . $insertObserverStmt->error;
                }
                $insertObserverStmt->close();
            }
        }

        function addNotification($conn, $user_id, $message) {
            $notifQuery = "INSERT INTO tbl_notification (user_id, message, type, link, is_read) 
                           VALUES (?, ?, 'info', 'evaluation.php', 0)";
            $notifStmt = $conn->prepare($notifQuery);
            if (!$notifStmt) {
                log_error("Failed to prepare notification statement: " . $conn->error);
                throw new Exception("Failed to prepare notification query.");
            }
            $notifStmt->bind_param("is", $user_id, $message);
            if (!$notifStmt->execute()) {
                log_error("[NOTIFICATION ERROR] Failed to add notification: " . $notifStmt->error);
            }
            $notifStmt->close();
        }

        // Determine notification message based on conditions
        if ($ePortfolioUpdateSuccess === true || $observerGradesSuccess === true) {
            $messages = [];

            if ($ePortfolioUpdateSuccess && $observerGradesSuccess) {
                if (($currentEPortfolioGrade == null || $currentEPortfolioGrade == 0) && 
                    ($currentFinalDemoAverage == null || $currentFinalDemoAverage == 0)) {
                    $messages[] = "Your eportfolio has been graded and final demo grade has been updated.";
                } elseif ($currentEPortfolioGrade == null || $currentEPortfolioGrade == 0) {
                    $messages[] = "Your eportfolio has been graded and your final demo grade has been updated.";
                } elseif ($currentFinalDemoAverage == null || $currentFinalDemoAverage == 0) {
                    $messages[] = "Your eportfolio grade has been updated and final demo grade has been graded.";
                } else {
                    $messages[] = "Your eportfolio and final demo grade have been updated.";
                }
            } elseif ($ePortfolioUpdateSuccess) {
                if ($currentEPortfolioGrade == null || $currentEPortfolioGrade == 0) {
                    $messages[] = "Your eportfolio has been graded.";
                } else {
                    $messages[] = "Your eportfolio grade has been updated.";
                }
            } elseif ($observerGradesSuccess) {
                if ($currentFinalDemoAverage == null || $currentFinalDemoAverage == 0) {
                    $messages[] = "Your final demo grade has been graded.";
                } else {
                    $messages[] = "Your final demo grade has been updated.";
                }
            }

            // Add notifications
            foreach ($messages as $message) {
                addNotification($conn, $user_id, $message);
            }
        }


        if ($observerGradesSuccess) {
            foreach ($existingObserverNumbers as $observerNumber => $status) {
                if (!isset($updatedObserverNumbers[$observerNumber])) {
                    $setInactiveQuery = "UPDATE tbl_observer_grades SET isActive = 1 WHERE evaluation_id = ? AND observer_number = ?";
                    $setInactiveStmt = $conn->prepare($setInactiveQuery);
                    $setInactiveStmt->bind_param("ii", $evaluationID, $observerNumber);
                    $setInactiveStmt->execute();
                    $setInactiveStmt->close();
                }
            }
        }

    } catch (Exception $e) {
        log_error("Error in updating/inserting observer grade: " . $e->getMessage());
        $response['success'] = false;
        $response['error'] = "An error occurred while updating/inserting observer grade.";

    }

    $conn->close();

    // Return the JSON response
    echo json_encode($response);
} catch (Exception $e) {
    header('Location: ../index.php');
    exit();
}
?>
