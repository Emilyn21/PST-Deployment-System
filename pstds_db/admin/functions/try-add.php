<?php
include 'auth.php';

require '../../PHPMailer-master/src/Exception.php';
require '../../PHPMailer-master/src/PHPMailer.php';
require '../../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $firstName = trim($_POST['first_name']);
        $middleName = trim($_POST['middleName'] ?? '') ?: null;
        $lastName = trim($_POST['last_name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $studentNumber = $_POST['studentNumber'];
        $program = $_POST['program'];
        $major = $_POST['major'];
        $academicYear = $_POST['academic_year'];


        $conn->begin_transaction();

        $sqlCheck = "
            SELECT 
                (SELECT COUNT(*) FROM tbl_user WHERE email = ?) AS email_exists, 
                (SELECT COUNT(*) FROM tbl_pre_service_teacher WHERE student_number = ?) AS student_number_exists
        ";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("ss", $email, $studentNumber);
        $stmtCheck->execute();
        $stmtCheck->bind_result($emailExists, $studentNumberExists);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($emailExists > 0 && $studentNumberExists > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Both email and student number are already registered.']);
            exit();
        } elseif ($emailExists > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email is already registered.']);
            exit();
        } elseif ($studentNumberExists > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Student number is already registered.']);
            exit();
        }


        $sqlUser = "INSERT INTO tbl_user (first_name, middle_name, last_name, email, password, created_by, role, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pre-service teacher', NOW())";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("sssssi", $firstName, $middleName, $lastName, $email, $password, $user_id);

        if ($stmtUser->execute()) {
            $user_id = $stmtUser->insert_id;

            $sqlPreServiceTeacher = "INSERT INTO tbl_pre_service_teacher (student_number, user_id, program_id, major_id, academic_year_id) 
                                     VALUES (?, ?, ?, ?, (SELECT id FROM tbl_academic_year WHERE academic_year_name = ?))";
            $stmtPreServiceTeacher = $conn->prepare($sqlPreServiceTeacher);
            $stmtPreServiceTeacher->bind_param("siiii", $studentNumber, $user_id, $program, $major, $academicYear);

            if ($stmtPreServiceTeacher->execute()) {
                $sqlNotification = "INSERT INTO tbl_notification (user_id, message, link, type) 
                                    VALUES (?, ?, ?, 'info')";
                $stmtNotification = $conn->prepare($sqlNotification);
                $message = "You have been registered as a pre-service teacher. Update your profile now.";
                $link = "account.php";
                $stmtNotification->bind_param("iss", $user_id, $message, $link);

                if ($stmtNotification->execute()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                        $mail->Debugoutput = 'html'; // Enable verbose debug output
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => true,
                                'verify_peer_name' => true,
                                'allow_self_signed' => false,
                                'cafile' => 'C:/xampp/php/extras/ssl/cacert.pem',
                            ],
                        ];
                        $mail->Username = '#change to sample email';
                        $mail->Password = '#change to app password';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port = 465;

                        $mail->setFrom('#sender email', 'System');
                        $mail->addAddress($email, $firstName . ' ' . $lastName);

                        $mail->isHTML(true);
                        $mail->Subject = 'Registration Successful';
                        $mail->Body = 'Hello ' . $firstName . ',<br><br>Thank you for registering as a pre-service teacher.';

                        $mail->send();
                    } catch (Exception $e) {
                        echo json_encode(['status' => 'error', 'message' => 'Error sending email: ' . $e->getMessage()]);
                        exit();
                    }

                    $conn->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Pre-service teacher added successfully!']);
                } else {
                    $conn->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Error adding notification.']);
                }
                $stmtNotification->close();
            } else {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Error adding pre-service teacher details.']);
            }
            $stmtPreServiceTeacher->close();
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error creating user.']);
        }
        $stmtUser->close();
    } else {
        header('Location: ../index.php');
        exit();
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'A critical error occurred.']);
} finally {
    $conn->close();
}

?>
