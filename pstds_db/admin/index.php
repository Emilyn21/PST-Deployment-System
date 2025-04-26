<?php 
include 'includes/auth.php';

$semesterType = ''; // Initialize the semester type variable

// Prepare query to get the active semester
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

if ($semesterRow = $semesterResult->fetch_assoc()) {
    $activeSemesterId = $semesterRow['id'];
    $semesterType = $semesterRow['type']; // Get semester type directly

    // Main query to fetch pre-service teacher details
    $stmt = $conn->prepare("SELECT DISTINCT
                    tu.email, 
                    tpst.student_number,
                    CONCAT(tu.last_name, ', ', tu.first_name, 
                    CASE 
                        WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
                        ELSE ''
                    END) AS student_name,
                    CONCAT(
                        COALESCE(tp.program_abbreviation, 'N/A'),
                        CASE 
                            WHEN tm.major_abbreviation IS NOT NULL THEN CONCAT('-', tm.major_abbreviation)
                            ELSE ''
                        END
                    ) AS program_major,
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
                    CONCAT(
                    COALESCE(tp.program_abbreviation, 'N/A'),
                        CASE 
                            WHEN tm.major_abbreviation IS NOT NULL THEN CONCAT('-', tm.major_abbreviation)
                            ELSE ''
                        END
                    ) AS program_major, 
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
                    tpl.start_date, tpl.end_date
                FROM 
                    tbl_placement tpl
                JOIN
                    tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
                INNER JOIN 
                    tbl_user tu ON tpst.user_id = tu.id
                INNER JOIN 
                    tbl_semester ts ON tpst.semester_id = ts.id
                INNER JOIN
                    tbl_academic_year ta ON ts.academic_year_id = ta.id
                INNER JOIN 
                    tbl_program tp ON tpst.program_id = tp.id
                LEFT JOIN 
                    tbl_major tm ON tpst.major_id = tm.id
                JOIN 
                    tbl_school tcs ON tpl.school_id = tcs.id
                LEFT JOIN 
                    tbl_adviser_assignment taa ON taa.placement_id = tpl.id
                LEFT JOIN 
                    tbl_adviser tadv ON tadv.id = taa.adviser_id
                LEFT JOIN 
                    tbl_user tadvu ON tadv.user_id = tadvu.id
                LEFT JOIN 
                    tbl_cooperating_teacher_assignment tcta ON tpl.id = tcta.placement_id
                LEFT JOIN 
                    tbl_cooperating_teacher ct ON ct.id = tcta.cooperating_teacher_id
                LEFT JOIN 
                    tbl_user tctu ON tctu.id = ct.user_id
                WHERE 
                    tpl.isDeleted = 0
                    AND tpl.start_date <= CURDATE() AND tpl.end_date >= CURDATE() 
                    AND tu.isDeleted = 0
                    AND tpl.status = 'approved'
                    AND tpst.semester_id = ?
                ORDER BY 
                    tpl.end_date DESC");

    $stmt->bind_param('i', $activeSemesterId);
    $stmt->execute();
    $result = $stmt->get_result();
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
    <title>Dashboard - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
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
                    // Fetch total number of pre-service teachers
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as total 
                        FROM tbl_pre_service_teacher tpst
                        JOIN tbl_user u ON tpst.user_id = u.id
                        WHERE u.isDeleted = 0 AND tpst.semester_id = ?");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $totalTeachersResult = $stmt->get_result();
                    $totalTeachers = $totalTeachersResult->fetch_assoc()['total'];
                    $stmt->close();

                    // Fetch total approved placements
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS total_approved_placements 
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
                        JOIN tbl_user tu ON tpst.user_id = tu.id
                        WHERE tpl.status = 'approved' 
                          AND tpl.isDeleted = 0 
                          AND tu.isDeleted = 0
                          AND tpst.semester_id = ?");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $resultApprovedPlacements = $stmt->get_result();
                    $totalApprovedPlacements = $resultApprovedPlacements->fetch_assoc()['total_approved_placements'];
                    $stmt->close();

                    // Fetch total approved visits
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS total_approved_visits 
                        FROM tbl_visit 
                        WHERE status = 'confirmed' 
                          AND isDeleted = 0");
                    $stmt->execute();
                    $resultApprovedVisits = $stmt->get_result();
                    $totalApprovedVisits = $resultApprovedVisits->fetch_assoc()['total_approved_visits'];
                    $stmt->close();

                    // Fetch total cooperating schools with active status
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) AS total_schools 
                        FROM tbl_school 
                        WHERE status = 'active' 
                          AND isDeleted = 0");
                    $stmt->execute();
                    $resultTotalSchools = $stmt->get_result();
                    $totalSchools = $resultTotalSchools->fetch_assoc()['total_schools'];
                    $stmt->close();
                    ?>

                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-3" role="region" aria-label="Total Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users me-2"></i>
                                        <span style="font-size: 1rem;">Total Pre-Service Teachers</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalTeachers ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="manage-pre-service-teacher.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-3" role="region" aria-label="Total Approved Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span style="font-size: 1rem;">Total Approved Placements</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalApprovedPlacements ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="manage-pst-placement.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-3" role="region" aria-label="Total Approved Visits">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <span style="font-size: 1rem;">Total Approved Visits</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalApprovedVisits ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="manage-visit.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-3" role="region" aria-label="Total Cooperating Schools">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-school me-2"></i>
                                        <span style="font-size: 1rem;">Total Cooperating Schools</span>
                                    </div>
                                    <h3 class="mb-0" style="font-size: 1.5rem; white-space: nowrap; padding-left: 0.5rem;">
                                        <?= $totalSchools ?>
                                    </h3>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="manage-cooperating-school.php">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    include '../connect.php';

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
                            JOIN tbl_user tu ON tpst.user_id = tu.id
                            WHERE tay.isDeleted = 0
                                AND tu.isDeleted = 0
                            GROUP BY tay.academic_year_name
                        ");
                        $stmt->execute();
                        $acadYearResult = $stmt->get_result();

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
                            LEFT JOIN tbl_placement tpl ON tpst.id = tpl.pre_service_teacher_id
                            JOIN tbl_user tu ON tpst.user_id = tu.id
                            WHERE tp.status = 'active'
                                AND tp.isDeleted = 0
                                AND tu.isDeleted = 0
                                AND tpl.isDeleted = 0
                                AND tpl.status = 'approved'
                            GROUP BY tp.program_abbreviation
                        ");
                        $stmt->execute();
                        $programResult = $stmt->get_result();

                        while ($row = $programResult->fetch_assoc()) {
                            $programs[] = $row['program_abbreviation'];
                            $counts[] = $row['count'];
                        }

                        $stmt->close();
                    } catch (Exception $e) {
                        // Handle errors
                        error_log('Error fetching data: ' . $e->getMessage());
                    }

                    // Fallback for no data
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
                                        <th scope="col" role="columnheader">Student Number</th>
                                        <th scope="col" role="columnheader">Pre-Service Teacher Name</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Placement School</th>
                                        <th scope="col" role="columnheader">Start Date</th>
                                        <th scope="col" role="columnheader">End Date</th>
                                        <th scope="col" role="columnheader">Adviser</th>
                                        <th scope="col" role="columnheader">Cooperating Teacher</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup"> 
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr role="row">
                                        <td class="text-center" role="cell"><?= htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                        noRows: "No active placements have been added yet."
                    },
                    perPage: 10, // Default entries per page
                    perPageSelect: [10, 25, 50, 100, -1] // Includes 50 and "All"
                });
                // Modify "-1" to show as "All" in dropdown
                setTimeout(() => {
                    document.querySelectorAll(".datatable-dropdown option").forEach(option => {
                        if (option.value == "-1") {
                            option.textContent = "All"; // Change "-1" to "All"
                        }
                    });
                }, 100);
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
                labels: <?= json_encode($academic_years); ?>, // Academic years
                datasets: [{
                    label: "Registrations",
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
                    data: <?= json_encode($registration_counts); ?>, // Number of registrations per year
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
                            max: Math.max(...<?= json_encode($registration_counts); ?>) + 10, // Dynamic max value
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
