<?php
include 'auth.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? $data['id'] : (isset($_POST['id']) ? $_POST['id'] : null);

    if ($id) {
            $conn->begin_transaction();

            try {
                // Mark the subject area as deleted
                $stmt = $conn->prepare("UPDATE tbl_subject_area SET isDeleted = 1 WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'The subject area has been deleted successfully.';
                } else {
                    $conn->rollback();
                    $response['status'] = 'error';
                    $response['message'] = 'Failed to delete the subject area.';
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $response['status'] = 'error';
                $response['message'] = 'An error occurred during deletion.';
            }
        }
    } else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
mysqli_close($conn);
?>
