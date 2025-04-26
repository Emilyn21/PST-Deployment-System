<?php
include 'auth.php';
include 'send-email.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = isset($_POST['first_name']) ? strtoupper(trim($_POST['first_name'])) : '';
    $middleName = (isset($_POST['middle_name']) && $_POST['middle_name'] !== '' && $_POST['middle_name'] !== 'null') 
        ? strtoupper(trim($_POST['middle_name'])) 
        : null;
    $lastName = isset($_POST['last_name']) ? strtoupper(trim($_POST['last_name'])) : '';
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $studentNumber = $_POST['studentNumber'];
    $program = $_POST['program'];
    $major = ($_POST['major'] === 'null') ? null : $_POST['major'];
    $semesterId = $_POST['semesterId'];

    // Generate password: 'cvsu' + 6 random alphanumeric characters
    $randomSuffix = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
    $passwordPlain = "cvsu" . $randomSuffix;
    $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);

    $conn->begin_transaction();

    try {
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

        $errors = [];

        if ($emailExists > 0) {
            $errors[] = [
                'field' => 'email',
                'message' => 'This email address is already registered.'
            ];
        }

        if ($studentNumberExists > 0) {
            $errors[] = [
                'field' => 'studentNumber',
                'message' => 'This student number is already registered.'
            ];
        }

        // If there are any errors, return them
        if (!empty($errors)) {
            echo json_encode([
                'status' => 'error',
                'errors' => $errors
            ]);
            exit();
        }

        $sqlUser = "INSERT INTO tbl_user (first_name, middle_name, last_name, email, password, created_by, role, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pre-service teacher', NOW())";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("sssssi", $firstName, $middleName, $lastName, $email, $passwordHash, $user_id);

        if ($stmtUser->execute()) {
            $user_id = $stmtUser->insert_id;

            $sqlPreServiceTeacher = "INSERT INTO tbl_pre_service_teacher (student_number, user_id, program_id, major_id, semester_id) 
                                     VALUES (?, ?, ?, ?, ?)";
            $stmtPreServiceTeacher = $conn->prepare($sqlPreServiceTeacher);
            $stmtPreServiceTeacher->bind_param("siiii", $studentNumber, $user_id, $program, $major, $semesterId);

            if ($stmtPreServiceTeacher->execute()) {
                $sqlNotification = "INSERT INTO tbl_notification (user_id, message, link, type) 
                                    VALUES (?, ?, ?, 'info')";
                $stmtNotification = $conn->prepare($sqlNotification);
                $message = "You have been registered as a pre-service teacher. Update your profile now.";
                $link = "account.php";
                $stmtNotification->bind_param("iss", $user_id, $message, $link);

                if ($stmtNotification->execute()) {
                    $conn->commit();
                    
                    // Try sending the email
                    $emailSent = sendRegistrationEmail($email, $firstName, $lastName, $passwordPlain);

                    // Return a single response, ensuring all data is in JSON format
                    echo json_encode([
                        'status' => $emailSent ? 'success' : 'error',
                        'message' => $emailSent 
                            ? 'Pre-service teacher added successfully!' 
                            : 'User added, but failed to send email. Please contact support.'
                    ]);
                } else {
                    $conn->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Error sending notification. Please try again.']);
                }
                $stmtNotification->close();
            } else {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Error adding pre-service teacher details. Please try again.']);
            }
            $stmtPreServiceTeacher->close();
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error creating user. Please try again.']);
        }
        $stmtUser->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
