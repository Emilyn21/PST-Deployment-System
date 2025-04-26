<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../../connect.php';

if (basename($_SERVER['PHP_SELF']) == 'auth.php') {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$sqlRoleCheck = "SELECT role FROM tbl_user WHERE id = ?";
$stmt = $conn->prepare($sqlRoleCheck);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_role = $user['role'];

switch ($user_role) {
    case 'adviser':
        break;
    case 'admin':
        header('Location: ../admin/index.php');
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
?>