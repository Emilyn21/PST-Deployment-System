<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['school_name'], $_POST['short_name'], $_POST['street'], $_POST['barangay'], $_POST['city'], $_POST['province'], $_POST['school_type'], $_POST['grade_levels'], $_POST['status'])) {
        
        $id = $_POST['id'];
        $school_name = $_POST['school_name'];
        $short_name = $_POST['short_name'];
        $street = strtoupper(trim($_POST['street'] ?? '')) ?: null;
        $barangay = strtoupper(trim($_POST['barangay']));
        $city = strtoupper(trim($_POST['city']));
        $province = strtoupper(trim($_POST['province']));
        $school_type = $_POST['school_type'];
        $grade_levels = implode(',', $_POST['grade_levels']);
        $status = $_POST['status'];

        $conn->begin_transaction();

        try {
            $checkStmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM tbl_school 
                WHERE (school_name = ? OR short_name = ?)
                AND city = ? 
                AND province = ? 
                AND id != ?
                AND isDeleted = 0
            ");
            $checkStmt->bind_param("sssii", $school_name, $short_name, $city, $province, $id);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                $response['status'] = 'error';
                $response['message'] = 'The school name or short name already exists in this city and province. Please provide a unique name or check the address details.';
            } else {
                $stmt = $conn->prepare("UPDATE tbl_school 
                        SET school_name = ?, 
                            short_name = ?, 
                            street = ?, 
                            barangay = ?, 
                            city = ?, 
                            province = ?, 
                            school_type = ?, 
                            grade_level = ?, 
                            status = ?
                        WHERE id = ?");
                $stmt->bind_param("sssssssssi", $school_name, $short_name, $street, $barangay, $city, $province, $school_type, $grade_levels, $status, $id);

                if ($stmt->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'The school details have been successfully updated.';
                } else {
                    $conn->rollback();
                    $response['message'] = 'An error occurred while updating the school details. Please try again later.';
                }

                $stmt->close();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Something went wrong during the update process. Please try again later.';
        }
    } else {
        $response['message'] = 'Please fill in all the required fields.';
    }
} else {
    header('Location: ../index.php');
    exit();
}

mysqli_close($conn);
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
