<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clean and normalize input
    $schoolName = trim($_POST['school_name']);
    $shortName = trim($_POST['short_name']);
    $schoolType = trim($_POST['school_type']);
    $gradeLevels = $_POST['grade_levels'];
    $street = strtoupper(!empty($_POST['street']) ? trim($_POST['street']) : null);
    $barangay = strtoupper(!empty($_POST['barangay']) ? trim($_POST['barangay'] : null);
    $municipality = strtoupper(!empty($_POST['municipality'] ? trim($_POST['municipality']) : null;
    $province = strtoupper(!empty($_POST['province'] ? trim($_POST['province']) : null;

    $gradeLevelsString = implode(',', $gradeLevels);

    try {
        $conn->begin_transaction();

        $checkSchool = $conn->prepare("SELECT id FROM tbl_school WHERE (school_name = ? OR short_name = ?) AND city = ? AND province = ?");
        $checkSchool->bind_param("ssss", $schoolName, $shortName, $municipality, $province);
        $checkSchool->execute();
        $result = $checkSchool->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'This school is already registered in the same municipality/province.']);
            $checkSchool->close();
            $conn->rollback();
            exit();
        }
        $checkSchool->close();

        $insertSchoolQuery = $conn->prepare("INSERT INTO tbl_school (school_name, short_name, street, barangay, city, province, school_type, grade_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertSchoolQuery->bind_param("ssssssss", $schoolName, $shortName, $street, $barangay, $municipality, $province, $schoolType, $gradeLevelsString);

        if (!$insertSchoolQuery->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add school. Please try again.']);
            $insertSchoolQuery->close();
            $conn->rollback();
            exit();
        }

        $insertSchoolQuery->close();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'School added successfully!']);
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
