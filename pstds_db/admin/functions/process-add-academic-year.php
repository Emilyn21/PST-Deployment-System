<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $academicYear = $_POST['academic_year'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    $conn->begin_transaction();

    try {
        $checkAcademicYear = $conn->prepare("SELECT * FROM tbl_academic_year WHERE academic_year_name = ? AND isDeleted = 0");
        $checkAcademicYear->bind_param("s", $academicYear);
        $checkAcademicYear->execute();
        $result = $checkAcademicYear->get_result();

        if ($result->num_rows > 0) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'This academic year name is already registered.']);
            exit();
        }

        $insertAcademicYearQuery = $conn->prepare("INSERT INTO tbl_academic_year (academic_year_name, start_date, end_date) VALUES (?, ?, ?)");
        $insertAcademicYearQuery->bind_param("sss", $academicYear, $startDate, $endDate);

        if ($insertAcademicYearQuery->execute()) {
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Academic year added successfully!']);
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to add academic year. Please try again.']);
        }

        $insertAcademicYearQuery->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
    }

    $checkAcademicYear->close();
    $conn->close();
} else {
    header('Location: ../add-academic-year.php');
    exit();
}
?>
