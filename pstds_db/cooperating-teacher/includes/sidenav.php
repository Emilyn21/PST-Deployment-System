<?php
include '../connect.php';

function getUserFullName($conn, $user_id) {
    $sql = "
        SELECT 
            CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(last_name, '')) AS fullname
        FROM tbl_user
        WHERE id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error in preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        return $user_data['fullname'] ?? 'Unknown User';
    } else {
        return 'Unknown User';
    }
}

// Example usage:
if (basename($_SERVER['PHP_SELF']) == 'sidenav.php') {
    header('Location: ../index.php');
    exit();
}

if (isset($_SESSION['user_id'])) {
    try {
        $user_id = $_SESSION['user_id'];
        $user_name = getUserFullName($conn, $user_id);
    } catch (Exception $e) {
        echo $e->getMessage();
        exit();
    }
} else {
    header('Location: ../login.php');
    exit();
}
?>
<div id="layoutSidenav_nav" class="mt-3">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <!-- Core Section -->
                <a class="nav-link" href="index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                <a class="nav-link" href="account.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-circle"></i></div>
                    Account
                </a>
                <a class="nav-link" href="announcement.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-bullhorn"></i></div>
                    Announcements
                </a>


                <!-- Pre-Service Teachers -->
                <div class="sb-sidenav-menu-heading">Pre-Service Teachers</div>
                <a class="nav-link" href="assigned-pre-service-teacher.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    Assigned Pre-Service Teachers
                </a>
                <a class="nav-link" href="attendance-records.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                    Attendance
                </a>
                <a class="nav-link" href="internship-evaluation.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    Internship Performance
                </a>
            </div>
        </div>
        <div class="sb-sidenav-footer">
        </div>
    </nav>
</div>
<style>
    .sb-sidenav-menu-heading {
        font-family: 'Segoe UI';
    }
</style>