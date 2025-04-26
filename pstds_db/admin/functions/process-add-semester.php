<?php
include 'auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academicYearId = intval($_POST['academic_year']);
    $type = htmlspecialchars($_POST['type']);
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    if (strtotime($startDate) >= strtotime($endDate)) {
        echo json_encode(['status' => 'error', 'message' => 'The start date must be earlier than the end date.']);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if the semester type already exists
        $checkSemester = $conn->prepare("
            SELECT * FROM tbl_semester 
            WHERE academic_year_id = ? AND type = ? AND isDeleted = 0
        ");
        if (!$checkSemester) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $checkSemester->bind_param("is", $academicYearId, $type);
        $checkSemester->execute();
        $result = $checkSemester->get_result();

        if ($result->num_rows > 0) {
            throw new Exception('The semester type "' . htmlspecialchars($type) . '" already exists for the selected academic year.');
        }

        // Insert the semester
        $insertSemester = $conn->prepare("
            INSERT INTO tbl_semester (academic_year_id, type, start_date, end_date) 
            VALUES (?, ?, ?, ?)
        ");
        if (!$insertSemester) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $insertSemester->bind_param("isss", $academicYearId, $type, $startDate, $endDate);

        if (!$insertSemester->execute()) {
            throw new Exception('Failed to add semester. Please try again.');
        }

        // Commit the transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Semester added successfully!']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } finally {
        // Close statements
        if (isset($checkSemester)) $checkSemester->close();
        if (isset($insertSemester)) $insertSemester->close();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
