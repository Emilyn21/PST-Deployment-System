<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../connect.php';

// Set session timeout duration (in seconds)
$timeout_duration = 900;

// Check if last activity is set and if the timeout has been exceeded
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit();
}

$_SESSION['last_activity'] = time();

if (basename($_SERVER['PHP_SELF']) == 'auth.php') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check the logged-in user's role
$user_id = $_SESSION['user_id'];
$sqlRoleCheck = "SELECT role FROM tbl_user WHERE id = ?";
$stmt = $conn->prepare($sqlRoleCheck);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_role = $user['role'];

// Redirect based on the user's role
switch ($user_role) {
    case 'admin':
        break;
    case 'adviser':
        header('Location: ../adviser/index.php');
        exit();
    case 'pre-service teacher':
        header('Location: ../pre-service-teacher/index.php');
        exit();
    case 'school_admin':
        header('Location: ../school-admin/index.php');
        exit();
    case 'cooperating_teacher':
        header('Location: ../cooperating-teacher/index.php');
        exit();
    default:
        // Redirect to login or error page if the role is unknown
        header('Location: ../login.php');
        exit();
}

$picquery = "SELECT 
            tu.profile_picture
          FROM tbl_user tu
          WHERE tu.id = ?";

if ($stmt = $conn->prepare($picquery)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $profile_picture = $row['profile_picture'];
    }
    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}
?>
