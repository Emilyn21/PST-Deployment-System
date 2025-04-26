<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean and normalize input
    $subjectAreaName = trim($_POST['subject-area-name']);
    $subjectAreaDescription = !empty($_POST['subject-area-description']) ? trim($_POST['subject-area-description']) : null;
    $programs = isset($_POST['programs']) && is_array($_POST['programs']) ? $_POST['programs'] : [];
    $programsString = !empty($programs) ? implode(', ', $programs) : null;

    try {
        $conn->begin_transaction();

        $insertSubjectAreaQuery = $conn->prepare("INSERT INTO tbl_subject_area (subject_area_name, subject_area_description, related_program) VALUES (?, ?, ?)");
        $insertSubjectAreaQuery->bind_param("sss", $subjectAreaName, $subjectAreaDescription, $programsString);

        if (!$insertSubjectAreaQuery->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add school. Please try again.']);
            $insertSubjectAreaQuery->close();
            $conn->rollback();
            exit();
        }

        $insertSubjectAreaQuery->close();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Subject area added successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again later.']);
    } finally {
        $conn->close();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
