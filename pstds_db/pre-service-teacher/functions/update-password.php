<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current-password']);
    $new_password = trim($_POST['new-password']);
    $confirm_password = trim($_POST['confirm-password']);

    // Fetch user data
    $sql = "SELECT password FROM tbl_user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $_SESSION['password_update_error'] = "User not found.";
        header("Location: ../account.php");
        exit();
    }

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['password_update_error'] = "Incorrect current password.";
        header("Location: ../account.php");
        exit();
    }

    // Check if new password matches confirm password
    if ($new_password !== $confirm_password) {
        $_SESSION['password_update_error'] = "New passwords do not match.";
        header("Location: ../account.php");
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in the database
    $sql_update = "UPDATE tbl_user SET password = ?, updated_at = NOW() WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param('si', $hashed_password, $user_id);

    if ($stmt_update->execute()) {
        $_SESSION['password_update_success'] = "Your password has been updated successfully.";
    } else {
        $_SESSION['password_update_error'] = "Failed to update password. Please try again.";
    }

    header("Location: ../account.php");
    exit();
} else {
    header("Location: ../../pre-service-teacher/index.php");
    exit();
}
?>