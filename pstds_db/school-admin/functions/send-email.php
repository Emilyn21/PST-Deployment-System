<?php

//YOUTUBE LINK: https://youtu.be/fIYyemqKR58?si=551J0u0K5hmG3vS9


require '../../PHPMailer-master/src/Exception.php';
require '../../PHPMailer-master/src/PHPMailer.php';
require '../../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send registration email to the user.
 *
 * @param string $recipientEmail
 * @param string $firstName
 * @param string $lastName
 * @param string $password
 * @return bool
 */
function sendRegistrationEmail($recipientEmail, $firstName, $lastName, $password, $role)
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

        //the SMTP password is fixed, FROM https://youtu.be/9Db7JtLht8I?si=SQ5BemtNv9BoRtlt&t=213
        // it's a generated password from google account, u can open the google account cvsu.pstds@gmail.com with password cvsupstds2025

        //Recipients
        $mail->setFrom('cvsu.pstds@gmail.com', 'CvSU Pre-Service Teacher Deployment System');
        $mail->addAddress($recipientEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Pre-Service Teaching Program';
        $mail->Body = "
            <h1>Welcome, $firstName $lastName!</h1>
            <p>You have been registered as <b>$role</b>.</p>
            <p>Here are your login credentials:</p>
            <ul>
                <li><b>Email:</b> $recipientEmail</li>
                <li><b>Password:</b> $password</li>
            </ul>
            <p>Please log in and change your password immediately.</p>
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