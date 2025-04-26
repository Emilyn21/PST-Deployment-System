<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $adviserId = $data['adviserId'];

    $conn->begin_transaction();

    try {
        $sql = "UPDATE tbl_user 
                SET isDeleted = 1, account_status = 'inactive', updated_at = NOW()
                WHERE id = (SELECT user_id FROM tbl_adviser WHERE id = ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $adviserId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete the adviser. Please try again.");
        }

        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => "The adviser has been successfully deleted."
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) $stmt->close();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
