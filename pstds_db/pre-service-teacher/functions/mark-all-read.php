<?php
include 'auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Read incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

$notification_id = $data['notification_id'] ?? null;

if ($notification_id === 'all') {
    // Mark all notifications as read for the user
    $sql = "UPDATE tbl_notification SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read.']);
    }
} else {
    if ($notification_id) {
        $sql = "UPDATE tbl_notification SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $notification_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read.']);
        }
    }
}

$stmt->close();
$conn->close();
?>
