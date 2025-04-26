<?php
include 'auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$notification_id) {
    header('Location: ../index.php');
    exit();
}

$sql = "UPDATE tbl_notification SET is_read = 1 WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $notification_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read.']);
    error_log("Database Error: " . $stmt->error);
}
?>
