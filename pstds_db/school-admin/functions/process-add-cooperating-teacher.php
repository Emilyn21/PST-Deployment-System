<?php
include 'auth.php';
include 'send-email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = isset($_POST['first_name']) ? strtoupper(trim($_POST['first_name'])) : '';
    $middle_name = (isset($_POST['middle_name']) && $_POST['middle_name'] !== '' && $_POST['middle_name'] !== 'null') 
        ? strtoupper(trim($_POST['middle_name'])) 
        : null;
    $last_name = isset($_POST['last_name']) ? strtoupper(trim($_POST['last_name'])) : '';
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject_area_id = $_POST['subject_area'];
    $role = 'cooperating teacher';

    // Generate password: 'cvsu' + 6 random alphanumeric characters
    $randomSuffix = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
    $passwordPlain = "cvsu" . $randomSuffix;
    $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);

    $conn->begin_transaction();

    try {
        // Check if the email already exists
        $checkEmail = $conn->prepare("SELECT COUNT(*) AS email_count FROM tbl_user WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmailResult = $checkEmail->get_result();
        $emailExists = $checkEmailResult->fetch_assoc()['email_count']; // Get the count of rows

        $errors = [];

        if ($emailExists > 0) {
            $errors[] = [
                'field' => 'email',
                'message' => 'This email address is already registered.'
            ];
        }

        if (!empty($errors)) {
            echo json_encode([
                'status' => 'error',
                'errors' => $errors // Return all email errors if any
            ]);
            exit();
        }

        $checkEmail->close();
        
        $sqlUser = "INSERT INTO tbl_user (first_name, middle_name, last_name, email, password, created_by, role, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'cooperating_teacher', NOW())";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("sssssi", $first_name, $middle_name, $last_name, $email, $passwordHash, $user_id);

        $schoolAdminSql = "SELECT school_id FROM tbl_school_admin WHERE user_id = ?";
        $stmtSchoolAdmin = $conn->prepare($schoolAdminSql);
        $stmtSchoolAdmin->bind_param("i", $user_id);
        $stmtSchoolAdmin->execute();
        $stmtSchoolAdmin->bind_result($school_id);
        $stmtSchoolAdmin->fetch();
        $stmtSchoolAdmin->close();

        if ($stmtUser->execute()) {
            $userId = $stmtUser->insert_id; 

            $sqlCooperatingTeacher = "INSERT INTO tbl_cooperating_teacher (user_id, subject_area_id, school_id) VALUES (?, ?, ?)";
            $stmtCooperatingTeacher = $conn->prepare($sqlCooperatingTeacher);
            $stmtCooperatingTeacher->bind_param("iii", $userId, $subject_area_id, $school_id);

            if ($stmtCooperatingTeacher->execute()) {
                $sqlNotification = "INSERT INTO tbl_notification (user_id, message, link, type) 
                                    VALUES (?, ?, ?, 'info')";
                $stmtNotification = $conn->prepare($sqlNotification);
                $message = "You have been registered as a cooperating teacher. Please review your profile.";
                $link = "account.php";
                $stmtNotification->bind_param("iss", $userId, $message, $link);

                if ($stmtNotification->execute()) {
                    $conn->commit();
                    // Try sending the email
                    $emailSent = sendRegistrationEmail($email, $first_name, $last_name, $passwordPlain, $role);
                    echo json_encode([
                        'status' => 'success', 
                        'message' => 'Cooperating teacher added successfully!',
                        'generated_password' => $passwordPlain // Putthis on comment after
                    ]);
                } else {
                    $conn->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Error sending notification. Please try again.']);
                }
                $stmtNotification->close();
            } else {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Error adding cooperating teacher details. Please try again.']);
            }
            $stmtCooperatingTeacher->close();
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
