<?php
include 'auth.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? $data['id'] : (isset($_POST['id']) ? $_POST['id'] : null);

    if ($id) {
        // Check for related entries in tbl_placement
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM tbl_placement 
            WHERE school_id = ? AND isDeleted = 0
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($placementCount);
        $stmt->fetch();
        $stmt->close();

        if ($placementCount > 0) {
            $response['status'] = 'error';
            $response['message'] = "Cannot delete the cooperating school because there are related placements.";
        } else {
            $conn->begin_transaction();

            try {
                // Mark the school as deleted
                $stmt = $conn->prepare("UPDATE tbl_school SET isDeleted = 1 WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'The school has been deleted successfully.';
                } else {
                    $conn->rollback();
                    $response['status'] = 'error';
                    $response['message'] = 'Failed to delete the school.';
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
        $response['message'] = 'Invalid request. School ID is missing.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
mysqli_close($conn);
?>
