<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the submitted data
    $studentNumber = $_POST['studentNumber'];
    $ePortfolioGrade = $_POST['ePortfolioGrade'];
    $internshipGrade = $_POST['internshipGrade'];
    $finalDemoGrades = $_POST['observerGrades'];
    $observerAttachments = $_FILES['observerAttachments'];

    $query = "SELECT placement_id FROM tbl_pre_service_teacher WHERE student_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $placementId = $row['placement_id'];

        $insertEvaluationQuery = "INSERT INTO tbl_evaluation (placement_id, eportfolio_grade, internship_grade, date_graded, created_by) VALUES (?, ?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE eportfolio_grade = ?, internship_grade = ?, date_graded = NOW()";
        $createdBy = $_SESSION['user_id'];
        $stmt = $conn->prepare($insertEvaluationQuery);
        $stmt->bind_param('iddiss', $placementId, $ePortfolioGrade, $internshipGrade, $createdBy, $ePortfolioGrade, $internshipGrade);
        $stmt->execute();

        $evaluationId = $conn->insert_id;

        foreach ($finalDemoGrades as $index => $grade) {
            $attachmentLink = null;
            if (isset($observerAttachments['name'][$index]) && $observerAttachments['name'][$index] !== '') {
                // Handle file upload
                $targetDir = "uploads/";
                $targetFile = $targetDir . basename($observerAttachments["name"][$index]);
                if (move_uploaded_file($observerAttachments["tmp_name"][$index], $targetFile)) {
                    $attachmentLink = $targetFile;
                }
            }

            $insertObserverGradeQuery = "INSERT INTO tbl_observer_grades (evaluation_id, observer_number, grade, attachment_link) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE grade = ?, attachment_link = ?";
            $observerNumber = $index + 1;
            $stmt = $conn->prepare($insertObserverGradeQuery);
            $stmt->bind_param('iisdss', $evaluationId, $observerNumber, $grade, $attachmentLink, $grade, $attachmentLink);
            $stmt->execute();
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
