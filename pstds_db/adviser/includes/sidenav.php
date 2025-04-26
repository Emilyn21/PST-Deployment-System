<?php
include '../connect.php';

// Example usage:
if (basename($_SERVER['PHP_SELF']) == 'sidenav.php') {
    header('Location: ../index.php');
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
                <!--a class="nav-link" href="manage-journal-entry.php">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-pen"></i></div>
                    Journal Entries
                </a-->
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <br>
        </div>
    </nav>
</div>
<style>
    .sb-sidenav-menu-heading {
        font-family: 'Segoe UI';
    }
</style>