<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['name'], $_POST['start_date'], $_POST['end_date'], $_POST['status'])) {

        $id = $_POST['id'];
        $name = $_POST['name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = $_POST['status'];

        $conn->begin_transaction();

        try {
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_academic_year WHERE academic_year_name = ? AND id != ? AND isDeleted = 0");
            $checkStmt->bind_param("si", $name, $id);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                $conn->rollback();
                $response['status'] = 'error';
                $response['message'] = 'The academic year name must be unique. Please enter a different name.';
            } else {
                $stmt = $conn->prepare("UPDATE tbl_academic_year 
                                        SET academic_year_name = ?, 
                                            start_date = ?, 
                                            end_date = ?, 
                                            status = ?
                                        WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $start_date, $end_date, $status, $id);

                if ($stmt->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'The academic year details have been successfully updated.';
                } else {
                    $conn->rollback();
                    $response['message'] = 'An unexpected error occurred. Please try again.';
                }

                $stmt->close();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'An unexpected error occurred. Please try again.';
        }
    } else {
        $response['message'] = 'An unexpected error occurred. Please try again later.';
    }
} else {
    header('Location: ../index.php');
    exit();
}

echo json_encode($response);
?>
