<?php
include '../connect.php';

header('Content-Type: application/json');

$response = ["success" => false, "message" => ""];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $response["message"] = "Passwords do not match.";
        echo json_encode($response);
        exit();
    }

    $stmt = $conn->prepare("SELECT email FROM tbl_password_reset WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response["message"] = "Invalid or expired token.";
        echo json_encode($response);
        exit();
    }

    $email = $result->fetch_assoc()['email'];
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $update_stmt = $conn->prepare("UPDATE tbl_user SET password = ? WHERE email = ?");
    $update_stmt->bind_param("ss", $hashed_password, $email);

    if ($update_stmt->execute()) {
        $delete_stmt = $conn->prepare("DELETE FROM tbl_password_reset WHERE email = ?");
        $delete_stmt->bind_param("s", $email);
        $delete_stmt->execute();

        $response["success"] = true;
    } else {
        $response["message"] = "Error updating password.";
    }
}

echo json_encode($response);
exit();
