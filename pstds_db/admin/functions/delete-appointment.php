<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['id'])) {
        $id = intval($data['id']);

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE tbl_visit SET isDeleted = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['status' => 'success']);
            } else {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Could not delete the visit.']);
            }

            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }

        exit;
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
