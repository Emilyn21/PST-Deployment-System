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

                <!-- Core Section>
                <div class="sb-sidenav-menu-heading">Core</div-->
                <a class="nav-link" href="index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>

                <!-- User Management Section -->
                <div class="sb-sidenav-menu-heading">User Management</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePST" aria-expanded="false" aria-controls="collapsePST">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    Pre-Service Teachers
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsePST" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-pre-service-teacher.php">Add PSTs</a>
                        <a class="nav-link" href="manage-pre-service-teacher.php">Manage PSTs</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAdviser" aria-expanded="false" aria-controls="collapseAdviser">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-chalkboard-user"></i></i></div>
                    Advisers
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseAdviser" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-adviser.php">Add Advisers</a>
                        <a class="nav-link" href="manage-adviser.php">Manage Advisers</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSchoolAdmin" aria-expanded="false" aria-controls="collapseSchoolAdmin">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-user-tie"></i></i></div>
                    School Admins
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseSchoolAdmin" aria-labelledby="headingThree" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-school-admin.php">Add School Admins</a>
                        <a class="nav-link" href="manage-school-admin.php">Manage School Admins</a>
                    </nav>
                </div>

                <!-- Communication Section -->
                <div class="sb-sidenav-menu-heading">Communication</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAnnouncement" aria-expanded="false" aria-controls="collapseAnnouncement">
                    <div class="sb-nav-link-icon"><i class="fas fa-bullhorn"></i></div>
                    Announcements
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseAnnouncement" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-announcement.php">Post Announcements</a>
                        <a class="nav-link" href="manage-announcement.php">Manage Announcements</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAppointment" aria-expanded="false" aria-controls="collapseAppointment">
                    <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                    Visits
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseAppointment" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="schedule-visit.php">Schedule Visit</a>
                        <a class="nav-link" href="manage-visit.php">Manage Visits</a>
                    </nav>
                </div>

                <!-- Placement and Assignment Section -->
                <div class="sb-sidenav-menu-heading">Placement Management</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePlacement" aria-expanded="false" aria-controls="collapsePlacement">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-location-pin"></i></div>
                    Pre-Service Teacher Placements
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsePlacement" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-pst-placement.php">Place PSTs</a>
                        <a class="nav-link" href="manage-pst-placement.php">Manage PST Placements</a>
                    </nav>
                </div>
                <a class="nav-link" href="assign-adviser.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-suitcase"></i></div>
                    Assign Adviser
                </a>

                <!-- Daily Tracking Section -->
                <div class="sb-sidenav-menu-heading">Daily Tracking and Evaluation</div>
                <a class="nav-link" href="attendance-records.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                    Attendance
                </a>

                <!--a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAttendance" aria-expanded="false" aria-controls="collapseAttendance">
                    <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                    Attendance
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseAttendance" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="student-attendance.php">Student Attendance</a>
                        <a class="nav-link" href="attendance-records.php">Attendance Records</a>
                    </nav>
                </div-->
                <!-- Program and Major Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseEvaluation" aria-expanded="false" aria-controls="collapseEvaluation">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    Evaluation
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseEvaluation" aria-labelledby="headingThree" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="manage-pst-evaluation.php">Pre-Service Teacher Evaluation</a>
                        <a class="nav-link" href="manage-criteria-percentage.php">Manage Evaluation Criteria</a>
                    </nav>
                </div>

                <!--a class="nav-link" href="manage-pst-evaluation.php">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    Evaluation
                </a-->

                <!-- Institutional Management Section -->
                <div class="sb-sidenav-menu-heading">Institutional Management</div>

                <!-- Program and Major Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProgram" aria-expanded="false" aria-controls="collapseProgram">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-book"></i></div>
                    Program and Major
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseProgram" aria-labelledby="headingThree" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-program.php">Add Program</a>
                        <a class="nav-link" href="manage-program.php">Manage Programs</a>
                        <a class="nav-link" href="add-major.php">Add Major</a>
                        <a class="nav-link" href="manage-major.php">Manage Majors</a>
                    </nav>
                </div>

                <!-- Academic Year Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAcadYear" aria-expanded="false" aria-controls="collapseAcadYear">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    Academic Year and Semester
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseAcadYear" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-academic-year.php">Add Academic Year</a>
                        <a class="nav-link" href="manage-academic-year.php">Manage Academic Years</a>
                        <a class="nav-link" href="add-semester.php">Add Semester</a>
                        <a class="nav-link" href="manage-semester.php">Manage Semesters</a>
                    </nav>
                </div>

                <!-- Cooperating Schools Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCoopSchool" aria-expanded="false" aria-controls="collapseCoopSchool">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-school"></i></div>
                    Cooperating Schools
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseCoopSchool" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-cooperating-school.php">Add Cooperating School</a>
                        <a class="nav-link" href="manage-cooperating-school.php">Manage Cooperating Schools</a>
                    </nav>
                </div>

                <!-- Subject Areas Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSubjectArea" aria-expanded="false" aria-controls="collapseSubjectArea">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-book-open"></i></div>
                    Subject Areas
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseSubjectArea" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-subject-area.php">Add Subject Area</a>
                        <a class="nav-link" href="manage-subject-area.php">Manage Subject Areas</a>
                    </nav>
                </div>

                <!-- Admins Section>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAdmin" aria-expanded="false" aria-controls="collapseAdmin">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-lock"></i></div>
                    Admins
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseAdmin" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="add-admin.php">Add Admins</a>
                        <a class="nav-link" href="manage-admin.php">Manage Admins</a>
                    </nav>
                </div-->
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