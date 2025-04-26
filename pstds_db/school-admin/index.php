<?php
include 'includes/auth.php';

$school_admin_query = "SELECT school_id FROM tbl_school_admin WHERE user_id = ?";
$stmt = $conn->prepare($school_admin_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$school_admin_result = $stmt->get_result();
if ($school_admin_data = $school_admin_result->fetch_assoc()) {
    $school_id = $school_admin_data['school_id'];
}
$stmt->close();

$semesterStmt = $conn->prepare("
    SELECT id, type
    FROM tbl_semester
    WHERE status = 'active' 
      AND isDeleted = 0 
    ORDER BY start_date DESC 
    LIMIT 1
");
$semesterStmt->execute();
$semesterResult = $semesterStmt->get_result();

if ($school_id) {
    $sqlAnnouncements = "
        SELECT id, title, created_at 
        FROM tbl_announcement 
        WHERE audience IN ('all', 'school_admin') 
            AND isDeleted = 0 
        ORDER BY created_at DESC 
        LIMIT 3
    ";
    $resultAnnouncements = $conn->query($sqlAnnouncements);

    // Fetch recent visits specific to the logged-in school
    $visitQuery = "
        SELECT title, visit_date, visit_time, status
        FROM tbl_visit
        WHERE school_id = ? AND isDeleted = 0 AND status != 'denied'
        ORDER BY visit_date DESC, visit_time DESC
        LIMIT 3
    ";
    $stmt = $conn->prepare($visitQuery);
    $stmt->bind_param('i', $school_id);
    $stmt->execute();
    $resultVisits = $stmt->get_result();
    $stmt->close();

    // Check if an active semester exists
    if ($semesterRow = $semesterResult->fetch_assoc()) {
        $activeSemesterId = $semesterRow['id'];
        $semesterType = $semesterRow['type'];

        // Fetch placements
        $stmt = $conn->prepare("
            SELECT 
                tpl.id AS placement_id, 
                tpst.student_number, 
                CONCAT(tu.last_name, ', ', tu.first_name, 
                    CASE 
                        WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
                        ELSE ''
                    END
                ) AS student_name,
                tu.email,
                tay.academic_year_name,
                CONCAT(
                    COALESCE(tp.program_abbreviation, 'N/A'),
                    CASE 
                        WHEN tm.major_abbreviation IS NOT NULL THEN CONCAT('-', tm.major_abbreviation)
                        ELSE ''
                    END
                ) AS program_major, 
                tpl.school_id, 
                tpl.status, 
                tpl.created_at, 
                tcs.school_name, 
                TRIM(
                    CONCAT(
                        tadvu.first_name, 
                        CASE 
                            WHEN tadvu.middle_name IS NOT NULL AND tadvu.middle_name != '' THEN CONCAT(' ', tadvu.middle_name) 
                            ELSE '' 
                        END, 
                        ' ', 
                        tadvu.last_name
                    )
                ) AS adviser_name,
                TRIM(
                    CONCAT(
                        tctu.first_name, 
                        CASE 
                            WHEN tctu.middle_name IS NOT NULL AND tctu.middle_name != '' THEN CONCAT(' ', tctu.middle_name) 
                            ELSE '' 
                        END, 
                        ' ', 
                        tctu.last_name
                    )
                ) AS ct_name,
                tpl.start_date, 
                tpl.end_date,
                tcta.date_assigned
            FROM 
                tbl_placement tpl
            JOIN tbl_pre_service_teacher tpst ON tpst.id = tpl.pre_service_teacher_id
            JOIN tbl_user tu ON tpst.user_id = tu.id
            JOIN tbl_semester ts ON tpst.semester_id = ts.id
            JOIN tbl_academic_year tay ON ts.academic_year_id = tay.id
            JOIN tbl_program tp ON tpst.program_id = tp.id
            LEFT JOIN tbl_major tm ON tpst.major_id = tm.id
            JOIN tbl_school tcs ON tpl.school_id = tcs.id
            LEFT JOIN tbl_cooperating_teacher_assignment tcta ON tcta.placement_id = tpl.id
            LEFT JOIN tbl_cooperating_teacher ct ON tcta.cooperating_teacher_id = ct.id
            LEFT JOIN tbl_user tctu ON ct.user_id = tctu.id
            LEFT JOIN tbl_adviser_assignment taa ON taa.placement_id = tpl.id
            LEFT JOIN tbl_adviser tadv ON taa.adviser_id = tadv.id
            LEFT JOIN tbl_user tadvu ON tadv.user_id = tadvu.id
            WHERE 
                tpl.school_id = ?
                AND tpl.start_date <= CURDATE() AND tpl.end_date >= CURDATE() 
                AND tpl.isDeleted = 0 
                AND tpl.status = 'approved'
                AND tu.isDeleted = 0
                AND tpst.semester_id = ?
            ORDER BY 
                tpl.end_date DESC
        ");

        $stmt->bind_param('ii', $school_id, $activeSemesterId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
    }
} else {
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Dashboard - School Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main role="main">
                <div class="container-fluid px-4">
                    <h1 class="mt-5 h3" id="main-heading">Dashboard</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as total 
                        FROM tbl_pre_service_teacher tpst
                        JOIN tbl_user tu ON tpst.user_id = tu.id
                        JOIN tbl_placement tpl ON tpst.id = tpl.pre_service_teacher_id
                        WHERE tu.isDeleted = 0 AND tpl.school_id = ?
                            AND tpl.status = 'approved' AND tpl.isDeleted = 0
                            AND tpst.semester_id = ?");
                    $stmt->bind_param('ii', $school_id, $activeSemesterId);
                    $stmt->execute();
                    $totalTeachersResult = $stmt->get_result();
                    $totalTeachers = $totalTeachersResult->fetch_assoc()['total'];
                    $stmt->close();

                    $stmt = $conn->prepare("SELECT COUNT(DISTINCT tpst.id) AS total_active_placements 
                                            FROM tbl_placement tpl
                                            JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
                                            JOIN tbl_user tu ON tpst.user_id = tu.id
                                            WHERE tpl.isDeleted = 0 AND tpl.status = 'approved'
                                                AND tu.isDeleted = 0 AND tu.account_status = 'active'
                                                AND tpl.school_id = ? AND tpst.semester_id = ?");
                    $stmt->bind_param("ii", $school_id, $activeSemesterId);
                    $stmt->execute();
                    $resultActivePlacements = $stmt->get_result();
                    $totalActivePlacements = $resultActivePlacements->fetch_assoc()['total_active_placements'];
                    $stmt->close();

                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS total_pending_placements 
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
                        WHERE tpl.status = 'pending' 
                          AND tpl.isDeleted = 0 
                          AND tpl.school_id = ?
                          AND tpst.semester_id = ?");
                    $stmt->bind_param('ii', $school_id, $activeSemesterId);
                    $stmt->execute();
                    $resultPendingPlacements = $stmt->get_result();
                    $totalPendingPlacements = $resultPendingPlacements->fetch_assoc()['total_pending_placements'];
                    $stmt->close();

                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS total_cooperating_teachers
                        FROM tbl_cooperating_teacher ct
                        JOIN tbl_user tu ON ct.user_id = tu.id
                        WHERE ct.school_id = ? AND tu.isDeleted = 0");
                    $stmt->bind_param('i', $school_id);
                    $stmt->execute();
                    $resultCooperatingTeacher = $stmt->get_result();
                    $totalCooperatingTeacher = $resultCooperatingTeacher->fetch_assoc()['total_cooperating_teachers'];
                    ?>

                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-3" role="region" aria-label="Total Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users me-2"></i> <!-- Icon with margin -->
                                        <span style="font-size: 1rem;">Total Pre-Service Teachers</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalTeachers ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="pst-placement-report.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-3" role="region" aria-label="Total Active Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clipboard-check me-2"></i> <!-- Icon for active placements -->
                                        <span style="font-size: 1rem;">Total Active Placements</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalActivePlacements ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="#activePlacementsTable">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-3" role="region" aria-label="Total Pending Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-hourglass-half me-2"></i> <!-- Icon for pending placements -->
                                        <span style="font-size: 1rem;">Total Pending Placements</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalPendingPlacements ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="manage-placement-request.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-3" role="region" aria-label="Total Cooperating Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-chalkboard-teacher me-2"></i> <!-- Icon for cooperating teachers -->
                                        <span style="font-size: 1rem;">Total Cooperating Teachers</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalCooperatingTeacher ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="manage-cooperating-teacher.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    include '../connect.php';

                    // Initialize variables
                    $programs = [];
                    $counts = [];
                    $academic_years = [];
                    $registration_counts = [];

                    try {

                        // Fetch data for the Area Chart (Pre-Service Teacher Registrations Over Years)
                        $stmt = $conn->prepare("
                        SELECT tay.academic_year_name, COUNT(tpst.id) AS count
                        FROM tbl_academic_year tay
                        LEFT JOIN tbl_semester ts ON ts.academic_year_id = tay.id
                        LEFT JOIN tbl_pre_service_teacher tpst ON tpst.semester_id = ts.id
                        JOIN tbl_placement tpl ON tpl.pre_service_teacher_id = tpst.id
                        JOIN tbl_user tu ON tpst.user_id = tu.id

                        WHERE tay.isDeleted = 0
                            AND tpl.school_id = ?
                            AND tpl.isDeleted = 0
                            AND tpl.status = 'approved'
                            AND tu.isDeleted = 0
                        GROUP BY tay.academic_year_name
                        ");

                        $stmt->bind_param('i', $school_id);
                        $stmt->execute();
                        $acadYearResult = $stmt->get_result();

                        $academic_years = [];
                        $registration_counts = [];

                        while ($row = $acadYearResult->fetch_assoc()) {
                            $academic_years[] = $row['academic_year_name'];
                            $registration_counts[] = $row['count'];
                        }
                        $stmt->close();

                        // Fetch data for the Bar Chart (Pre-Service Teachers Per Program)
                        $stmt = $conn->prepare("
                            SELECT tp.program_abbreviation, COUNT(tpst.id) AS count
                            FROM tbl_program tp
                            LEFT JOIN tbl_pre_service_teacher tpst ON tp.id = tpst.program_id
                            JOIN tbl_user tu ON tpst.user_id = tu.id
                            LEFT JOIN tbl_placement tpl ON tpst.id = tpl.pre_service_teacher_id
                            WHERE tp.status = 'active' 
                                AND tp.isDeleted = 0
                                AND tu.isDeleted = 0
                                AND tpl.school_id = ? 
                                AND tpl.isDeleted = 0
                                AND tpl.status = 'approved'
                            GROUP BY tp.program_abbreviation
                        ");
                        $stmt->bind_param('i', $school_id);
                        $stmt->execute();
                        $programResult = $stmt->get_result();

                        while ($row = $programResult->fetch_assoc()) {
                            $programs[] = $row['program_abbreviation'];
                            $counts[] = $row['count'];
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        error_log("Error fetching chart data: " . $e->getMessage());
                    }

                    // Fallback for empty data
                    if (empty($programs)) {
                        $programs = ['No Data'];
                        $counts = [0];
                    }

                    if (empty($academic_years)) {
                        $academic_years = ['No Data'];
                        $registration_counts = [0];
                    }

                    $conn->close();
                    ?>

                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4" role="region" aria-labelledby="recentAnnouncementsHeader">
                                <div class="card-header" id="recentAnnouncementsHeader">
                                    <i class="fas fa-clock me-1"></i>
                                    Recent Announcements
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <?php if ($resultAnnouncements->num_rows > 0): ?>
                                            <?php while ($announcement = $resultAnnouncements->fetch_assoc()): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                                    <small>
                                                        (<?php
                                                        $announcementDate = strtotime($announcement['created_at']);
                                                        $announcementYear = date('Y', $announcementDate);
                                                        echo date('M j', $announcementDate); // Always display month and day
                                                        if ($announcementYear !== $currentYear) {
                                                            echo ", $announcementYear"; // Add the year only if it's not the current year
                                                        }
                                                        echo ', ' . date('g:i A', $announcementDate);
                                                        ?>)
                                                    </small>
                                                </li>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            No announcements found.
                                        <?php endif; ?>
                                    </ul>
                                    <a href="announcement.php" class="btn btn-primary">View All Announcements</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card mb-4" role="region" aria-labelledby="recentVisitsHeader">
                                <div class="card-header" id="recentVisitsHeader">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Recent Visits
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                    <?php if ($resultVisits->num_rows > 0): ?>
                                        <?php while ($visit = $resultVisits->fetch_assoc()): ?>
                                            <li>
                                                <strong><?= htmlspecialchars($visit['title']) ?></strong>
                                                <small>(<?= date('M j, Y', strtotime($visit['visit_date']));?>
                                                at <?= date('g:i A', strtotime($visit['visit_time'])) ?>)</small>
                                                <!-- Badge for status -->
                                                <span class="badge 
                                                    <?php
                                                        switch ($visit['status']) {
                                                            case 'pending': echo 'bg-warning'; break;
                                                            case 'confirmed': echo 'bg-success'; break;
                                                            case 'denied': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                    ?>" style="margin-left: 5px;">
                                                    <?= htmlspecialchars($visit['status']) ?>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        No visits found.
                                    <?php endif; ?>
                                    </ul>
                                    <a href="manage-visit.php" class="btn btn-primary">View All Scheduled Visits</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4" role="region" aria-labelledby="chartAcademicYearHeader">
                                <div class="card-header" id="chartAcademicYearHeader">
                                    <i class="fas fa-chart-area me-1"></i>
                                    Pre-Service Teachers Per Academic Year
                                </div>
                                <div class="card-body">
                                    <canvas id="myAreaChart" width="100%" height="40" aria-label="Chart showing Pre-Service Teachers per Academic Year"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card mb-4" role="region" aria-labelledby="chartProgramHeader">
                                <div class="card-header" id="chartProgramHeader">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Pre-Service Teachers Per Program
                                </div>
                                <div class="card-body">
                                    <canvas id="myBarChart" width="100%" height="40" aria-label="Chart showing Pre-Service Teachers per Program"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4" id="activePlacementsTable">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Active Pre-Service Teacher Placements
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Active Pre-Service Teacher Placements Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Pre-Service Teacher Name</th>
                                        <th scope="col" role="columnheader">Program-Major</th>
                                        <th scope="col" role="columnheader">Start Date</th>
                                        <th scope="col" role="columnheader">End Date</th>
                                        <th scope="col" role="columnheader">Adviser</th>
                                        <th scope="col" role="columnheader">Cooperating Teacher</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup"> 
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= $row['start_date'] ? htmlspecialchars(date('M j, Y', strtotime($row['start_date'])), ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
                                            <td role="cell"><?= $row['end_date'] ? htmlspecialchars(date('M j, Y', strtotime($row['end_date'])), ENT_QUOTES, 'UTF-8') : 'N/A'; ?></td>
                                            <td role="cell">
                                                <?php 
                                                if ($row['adviser_name'] === 'Pending' || is_null($row['adviser_name'])) {
                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                } else {
                                                    echo htmlspecialchars($row['adviser_name'], ENT_QUOTES, 'UTF-8');
                                                }
                                                ?>
                                            </td>
                                            <td role="cell">
                                                <?php 
                                                if ($row['ct_name'] === 'Pending' || is_null($row['ct_name'])) {
                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                } else {
                                                    echo htmlspecialchars($row['ct_name'], ENT_QUOTES, 'UTF-8');
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                const dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No active placements have been assigned to the school yet."
                    }
                });
            }
        });
        // Bar Chart
        var ctx = document.getElementById("myBarChart");
        var myBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($programs); ?>, // Program names
                datasets: [{
                    label: "Pre-service Teachers",
                    backgroundColor: "rgba(2,117,216,1)",
                    borderColor: "rgba(2,117,216,1)",
                    data: <?= json_encode($counts); ?>, // Number of pre-service teachers
                }],
            },
            options: {
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 6
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            min: 0,
                            max: Math.max(...<?= json_encode($counts); ?>) + 10, // Dynamic max value
                            maxTicksLimit: 5
                        },
                        gridLines: {
                            display: true
                        }
                    }],
                },
                legend: {
                    display: false
                }
            }
        });

        // Area Chart Example
        var ctx = document.getElementById("myAreaChart");
        var myLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($academic_years); ?>,
                datasets: [{
                    label: "Pre-service Teachers",
                    lineTension: 0.3,
                    backgroundColor: "rgba(2,117,216,0.2)",
                    borderColor: "rgba(2,117,216,1)",
                    pointRadius: 5,
                    pointBackgroundColor: "rgba(2,117,216,1)",
                    pointBorderColor: "rgba(255,255,255,0.8)",
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "rgba(2,117,216,1)",
                    pointHitRadius: 50,
                    pointBorderWidth: 2,
                    data: <?= json_encode($registration_counts); ?>,
                }],
            },
            options: {
                scales: {
                    xAxes: [{
                        time: {
                            unit: 'date'
                        },
                        gridLines: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            min: 0,
                            max: Math.max(...<?= json_encode($registration_counts); ?>) + 10,
                            maxTicksLimit: 5
                        },
                        gridLines: {
                            color: "rgba(0, 0, 0, .125)",
                        }
                    }],
                },
                legend: {
                    display: false
                }
            }
        });
    </script>
</body>

</html>
