<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['visitId'];
    $school = $_POST['school'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $title = $_POST['title'];
    $content = $_POST['content'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE tbl_visit SET school_id = ?, visit_type_id = ?, visit_date = ?, visit_time = ?, title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $school, $type, $date, $time, $title, $content, $id);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error updating the visit. Please try again later.']);
        }

        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error updating the visit. Please try again later.']);
    } finally {
        $conn->close();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
