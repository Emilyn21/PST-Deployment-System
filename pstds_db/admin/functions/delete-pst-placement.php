<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['id'])) {
        $id = intval($data['id']);

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE tbl_placement SET isDeleted = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);

            $stmt2 = $conn->prepare("UPDATE tbl_pre_service_teacher SET placement_status = 'unplaced' WHERE id = (SELECT pre_service_teacher_id FROM tbl_placement WHERE id = ?)");
            $stmt2->bind_param("i", $id);

            // Execute both queries
            if ($stmt->execute() && $stmt2->execute()) {
                $conn->commit();
                echo json_encode(['status' => 'success']);
            } else {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Could not delete placement.']);
            }

            $stmt->close();
            $stmt2->close();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request. Placement ID is missing.']);
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
