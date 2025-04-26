<?php
include 'auth.php';

if (isset($_POST['notification_id']) && isset($_SESSION['user_id'])) {
    $notification_id = $_POST['notification_id'];
    $user_id = $_SESSION['user_id'];

    // Update the notification status to "deleted"
    $sql = "UPDATE tbl_notification SET status = 'deleted' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);

    if ($stmt->execute()) {
        // Redirect back to the notifications page or previous page
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        echo "Error updating notification status.";
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
