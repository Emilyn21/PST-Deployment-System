<?php
include 'auth.php';
include 'send-email.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = isset($_POST['first_name']) ? strtoupper(trim($_POST['first_name'])) : '';
    $middle_name = (isset($_POST['middle_name']) && $_POST['middle_name'] !== '' && $_POST['middle_name'] !== 'null') 
        ? strtoupper(trim($_POST['middle_name'])) 
        : null;
    $last_name = isset($_POST['last_name']) ? strtoupper(trim($_POST['last_name'])) : '';
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $school_id = (int) $_POST['school'];
    $role = 'school administrator';

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
                    VALUES (?, ?, ?, ?, ?, ?, 'school_admin', NOW())";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("sssssi", $first_name, $middle_name, $last_name, $email, $passwordHash, $user_id);

        if ($stmtUser->execute()) {
            $user_id = $stmtUser->insert_id;

            $sqlSchoolAdmin = "INSERT INTO tbl_school_admin (user_id, school_id) VALUES (?, ?)";
            $stmtSchoolAdmin = $conn->prepare($sqlSchoolAdmin);
            $stmtSchoolAdmin->bind_param("ii", $user_id, $school_id);

            if ($stmtSchoolAdmin->execute()) {
                // Fetch school name for notification
                $sqlSchoolName = "SELECT school_name FROM tbl_school WHERE id = ?";
                $stmtSchoolName = $conn->prepare($sqlSchoolName);
                $stmtSchoolName->bind_param("i", $school_id);
                $stmtSchoolName->execute();
                $stmtSchoolName->bind_result($school_name);
                $stmtSchoolName->fetch();
                $stmtSchoolName->close();
                
                $sqlNotification = "INSERT INTO tbl_notification (user_id, message, link, type) 
                                    VALUES (?, ?, ?, 'info')";
                $stmtNotification = $conn->prepare($sqlNotification);
                $message = "You have been registered as a school administrator for {$school_name}. Please review your profile.";
                $link = "account.php";
                $stmtNotification->bind_param("iss", $user_id, $message, $link);

                if ($stmtNotification->execute()) {
                    $conn->commit();
                    // Try sending the email
                    $emailSent = sendRegistrationEmail($email, $first_name, $last_name, $passwordPlain, $role);

                    echo json_encode([
                        'status' => 'success', 
                        'message' => 'School administrator added successfully!',
                        'generated_password' => $passwordPlain // Put this on comment after
                    ]);
                } else {
                    $conn->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Error adding notification. Please try again.']);
                }
                $stmtNotification->close();
            } else {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Error adding school administrator details. Please try again.']);
            }
            $stmtSchoolAdmin->close();
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