<?php
include 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['announcementId'])) {
    $announcementId = $_POST['announcementId'];

    $conn->begin_transaction();

    try {
        $sql = "UPDATE tbl_announcement SET isDeleted = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $announcementId);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    header('Location: ../index.php');
    exit();
}
?>
