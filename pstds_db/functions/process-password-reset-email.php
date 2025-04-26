<?php
session_start(); // Start session to store error message
include '../connect.php';
include 'send-email.php';

// Set the timezone to Philippine Time (PHT)
date_default_timezone_set('Asia/Manila');  

$errorMsg = ""; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Check if email exists
    $stmt = $conn->prepare("SELECT email FROM tbl_user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        
        // Get current timestamp with Philippine time
        $created_at = date("Y-m-d H:i:s");  
        $expires_at = date("Y-m-d H:i:s", strtotime($created_at . " +30 minutes"));

        // Insert into password reset table
        $stmt = $conn->prepare("INSERT INTO tbl_password_reset (email, token, expires_at, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $token, $expires_at, $created_at);

        if ($stmt->execute()) {
            // Create reset link
            $resetLink = "http://localhost/pstds_db/reset-link.php?token=" . $token;
            
            // Send email
            $emailSent = sendPasswordResetEmail($email, $resetLink);

            if ($emailSent) {
                header("Location: ../login.php?message=reset_sent");
                exit;
            } else {
                $_SESSION['errorMsg'] = "❌ Failed to send email. Please try again."; // Store error in session
                header("Location: ../password-recovery.php");
                exit;
            }
        }
    } else {
        $_SESSION['errorMsg'] = "⚠️ Email not found. Please enter a registered email."; // Store error in session
        header("Location: ../password-recovery.php");
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>
