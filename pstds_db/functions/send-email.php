<?php
require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send password reset email to the user.
 *
 * @param string $recipientEmail
 * @param string $resetLink
 * @return bool
 */
function sendPasswordResetEmail($recipientEmail, $resetLink)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->Username = 'cvsu.pstds@gmail.com'; // Replace with your email
        $mail->Password = 'pftnuxhetketdkzp'; // SMTP password

        //Recipients
        $mail->setFrom('cvsu.pstds@gmail.com', 'CvSU Pre-Service Teacher Deployment System');
        $mail->addAddress($recipientEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "
            <h1>Password Reset Request</h1>
            <p>Click the link below to reset your password:</p>
            <p><a href='$resetLink'>$resetLink</a></p>
            <p>If you did not request this, please ignore this email.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the error instead of echoing it
        error_log("Email failed to send: " . $mail->ErrorInfo);
        return false;
    }
}
?>