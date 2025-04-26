<?php
include '../connect.php';

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
                <!-- <div class="sb-sidenav-menu-heading">Core</div> -->
                <a class="nav-link" href="index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                <a class="nav-link" href="account.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-circle"></i></div>
                    Account
                </a>

                <!-- Communication -->
                <div class="sb-sidenav-menu-heading">Communication</div>
                <a class="nav-link" href="announcement.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-bullhorn"></i></div>
                    Announcements
                </a>
                <a class="nav-link" href="manage-visit.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-clock"></i></div>
                    Visits
                </a>

                <!-- Placement and Assignment -->
                <div class="sb-sidenav-menu-heading">Placement and Assignment</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePlacement" aria-expanded="false" aria-controls="collapsePlacement">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-friends"></i></div>
                    Pre-Service Teacher Placements
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsePlacement" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="manage-placement-request.php">Placement Requests</a>
                        <a class="nav-link" href="pst-placement-report.php">Placement Report</a>
                    </nav>
                </div>
                <a class="nav-link" href="assign-cooperating-teacher.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    Assign Cooperating Teacher
                </a>
                
                <!-- Account Management -->
                <div class="sb-sidenav-menu-heading">Account Management</div>

                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAdviser" aria-expanded="false" aria-controls="collapseAdviser">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-friends"></i></div>
                    Cooperating Teacher
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseAdviser" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-cooperating-teacher.php">Add Cooperating Teacher</a>
                        <a class="nav-link" href="manage-cooperating-teacher.php">Manage Cooperating Teacher</a>
                    </nav>
                </div>
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