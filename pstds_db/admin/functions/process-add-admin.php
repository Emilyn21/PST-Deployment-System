<?php
include 'auth.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize form data
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middleName'] ?? '') ?: null;
    $lastName = trim($_POST['last_name']);
    $user_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $user_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $user_role = 'admin'; // Role is hardcoded as 'admin' for this form

    // Check for duplicate email
    $emailCheckSql = "SELECT COUNT(*) FROM tbl_user WHERE email = ?";
    $stmtEmailCheck = $conn->prepare($emailCheckSql);
    $stmtEmailCheck->bind_param("s", $user_email);
    $stmtEmailCheck->execute();
    $stmtEmailCheck->bind_result($emailExists);
    $stmtEmailCheck->fetch();
    $stmtEmailCheck->close();

    if ($emailExists > 0) {
        $_SESSION['error_message'] = 'This email address is already registered. Please use a different email.';
        header('Location: ../add-admin.php');
        exit();
    } 

    // Proceed with insertion if no duplicates
    $sqlUser = "INSERT INTO tbl_user (first_name, middle_name, last_name, email, password, created_by, role, account_status) VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active')";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("sssssi", $firstName, $middleName, $lastName, $user_email, $user_password, $user_id);

    if ($stmtUser->execute()) {
        $user_id = $stmtUser->insert_id; // Get the last inserted user ID
        
        $insertAdminQuery = $conn->prepare("INSERT INTO tbl_admin (user_id) VALUES (?)");
        $insertAdminQuery->bind_param("i", $user_id);

        if ($insertAdminQuery->execute()) {
            $_SESSION['success_message'] = 'Admin added successfully!';
            header('Location: ../add-admin.php');
        } else {
            $_SESSION['error_message'] = 'Error adding admin details. Please try again.';
            header('Location: ../add-admin.php');
        }
        $insertAdminQuery->close();
    } else {
        $_SESSION['error_message'] = 'Error creating user. Please try again.';
        header('Location: ../add-admin.php');
    }
    $stmtUser->close();
} else {
    header('Location: ../index.php');
}
$conn->close();
?>
