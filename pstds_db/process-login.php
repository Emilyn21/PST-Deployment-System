<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Disable display of errors to avoid HTML output

// Include the database connection
include 'connect.php';

// Initialize an empty response
$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure both email and password are set
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        $response['message'] = 'Email address or password missing.';
        echo json_encode($response);
        exit();
    }

    // Retrieve and sanitize form data
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);

    // Prepare the SQL query to fetch the user data
    $query = $conn->prepare("SELECT * FROM tbl_user WHERE email = ?");
    $query->bind_param("s", $email);

    if ($query->execute()) {
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                $updateLastLoginQuery = $conn->prepare("UPDATE tbl_user SET last_login = NOW() WHERE id = ?");
                $updateLastLoginQuery->bind_param("i", $user['id']);
                $updateLastLoginQuery->execute();
                $updateLastLoginQuery->close();

                $response = [
                    'status' => 'success',
                    'redirect' => getRedirectUrl($user['role'])
                ];
            } else {
                $response['message'] = 'The password you entered is incorrect. Please try again.';
            }
        } else {
            $response['message'] = 'The email address is not registered. Please check and try again.';
        }

        $query->close();
    } else {
        $response['message'] = 'Database query failed. Please try again later.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);

function getRedirectUrl($role) {
    switch ($role) {
        case 'admin':
            return 'admin/index.php';
        case 'pre-service teacher':
            return 'pre-service-teacher/index.php';
        case 'school_admin':
            return 'school-admin/index.php';
        case 'adviser':
            return 'adviser/index.php';
        case 'cooperating_teacher':
            return 'cooperating-teacher/index.php';
        default:
            return 'index.php';
    }
}
?>