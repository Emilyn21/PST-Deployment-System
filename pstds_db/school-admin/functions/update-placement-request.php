<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placement_id = $_POST['placement_id'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    if (!$placement_id || !$start_date || !$end_date) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    $update_query = "UPDATE tbl_placement SET start_date = ?, end_date = ?, updated_at = CURRENT_TIMESTAMP() WHERE id = ? AND isDeleted = 0";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ssi', $start_date, $end_date, $placement_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>