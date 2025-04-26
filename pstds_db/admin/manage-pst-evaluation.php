<?php 
include 'includes/auth.php';

$academicYearName = '';
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

    // Fetch academic year for the active semester
    $stmtAcademicYear = $conn->prepare("
        SELECT tay.academic_year_name
        FROM tbl_academic_year tay
        INNER JOIN tbl_semester ts ON tay.id = ts.academic_year_id
        WHERE ts.id = ?
    ");
    $stmtAcademicYear->bind_param('i', $activeSemesterId);
    $stmtAcademicYear->execute();
    $academicYearResult = $stmtAcademicYear->get_result();

    if ($academicYearRow = $academicYearResult->fetch_assoc()) {
        $academicYearName = $academicYearRow['academic_year_name'];
    }

    $stmt = $conn->prepare("SELECT 
                tpst.id AS pst_id,
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
                tp.id AS program_id, 
                tm.major_name AS major, 
                tm.id AS major_id,
                tpl.id AS placement_id,
                te.id AS ePortfolio_id,
                te.grade AS ePortfolio_grade,
                te.file_link AS ePortfolio_link,
                tev.internship_grade,
                tev.final_demo_average,
                tev.overall_average,
                tog.observer_number,
                GROUP_CONCAT(tog.grade) AS observer_grade,
                tog.attachment_link AS og_link
            FROM 
                tbl_placement tpl
            JOIN 
                tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
            JOIN 
                tbl_user tu ON tpst.user_id = tu.id
            JOIN 
                tbl_program tp ON tpst.program_id = tp.id
            LEFT JOIN 
                tbl_major tm ON tpst.major_id = tm.id
            LEFT JOIN
                tbl_eportfolio te ON tpst.id = te.pre_service_teacher_id
            LEFT JOIN
                tbl_evaluation tev ON tpl.id = tev.placement_id
            LEFT JOIN
                tbl_observer_grades tog ON tev.id = tog.evaluation_id
            JOIN
                tbl_semester ts ON ts.id = tpst.semester_id
            JOIN
                tbl_academic_year tay ON tay.id = ts.academic_year_id    
            WHERE 
                tpl.isDeleted = 0
                AND tpl.status = 'approved'
                AND tu.isDeleted = 0
                AND tpst.semester_id = ?
            GROUP BY
                tpst.id
            ORDER BY 
                tu.last_name ASC");

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
    <title>Evaluation for Pre-Service Teachers - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        } @media (max-width: 576px) {
            .flex-wrap-nowrap {
                flex-wrap: wrap !important;
            }
        } .btn-action {
            margin-top: 0.2rem;
        } .form-group {
            margin-bottom: 0.15rem;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav" role="navigation">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main role="main">
                <div class="container-fluid px-4">
                    <h1 class="mt-5 h3" id="main-heading">Evaluation for Pre-Service Teachers</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Evaluation</li>
                    </ol>
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) AS total_placements 
                                   FROM tbl_placement tpl 
                                   JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                   JOIN tbl_user tu ON tpst.user_id = tu.id 
                                   JOIN tbl_semester ts ON ts.id = tpst.semester_id
                                   JOIN tbl_academic_year tay ON tay.id = ts.academic_year_id  
                                   WHERE tpl.isDeleted = 0 
                                     AND tu.isDeleted = 0 
                                     AND tu.account_status = 'active' 
                                     AND tpl.status = 'approved'
                                     AND tpst.semester_id = ?");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $totalResult = $stmt->get_result();
                    $totalPlacements = $totalResult->fetch_assoc()['total_placements'];
                    $stmt->close();

                    $stmt = $conn->prepare("SELECT AVG(tev.overall_average) as avgGrade 
                                          FROM tbl_evaluation tev
                                          JOIN tbl_placement tpl ON tev.placement_id = tpl.id
                                          JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                          JOIN tbl_user tu ON tpst.user_id = tu.id 
                                          JOIN tbl_semester ts ON ts.id = tpst.semester_id
                                          JOIN tbl_academic_year tay ON tay.id = ts.academic_year_id  
                                          WHERE tpl.isDeleted = 0 
                                            AND tev.overall_average != 0
                                            AND tu.isDeleted = 0 
                                            AND tu.account_status = 'active' 
                                            AND tpl.status = 'approved'
                                            AND tpst.semester_id = ?");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $averageGradeResult = $stmt->get_result();
                    $averageGrade = $averageGradeResult->fetch_assoc()['avgGrade'];
                    $stmt->close();

                    $stmt = $conn->prepare("SELECT COUNT(*) as totalGrades 
                                         FROM tbl_evaluation tev
                                         JOIN tbl_placement tpl ON tev.placement_id = tpl.id
                                         JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                         JOIN tbl_user tu ON tpst.user_id = tu.id 
                                         JOIN tbl_semester ts ON ts.id = tpst.semester_id
                                         JOIN tbl_academic_year tay ON tay.id = ts.academic_year_id  
                                         WHERE tpl.isDeleted = 0 
                                           AND tev.ePortfolio_grade IS NOT NULL 
                                           AND tev.internship_grade IS NOT NULL 
                                           AND tev.final_demo_average IS NOT NULL
                                           AND tu.isDeleted = 0 
                                           AND tu.account_status = 'active' 
                                           AND tpl.status = 'approved'
                                           AND tpst.semester_id = ?");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $totalGradesResult = $stmt->get_result();
                    $totalGrades = $totalGradesResult->fetch_assoc()['totalGrades'];
                    $stmt->close();

                    $stmt = $conn->prepare("SELECT COUNT(*) as totalMissingGrade
                                               FROM tbl_placement tpl
                                               JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                               LEFT JOIN tbl_evaluation tev ON tpl.id = tev.placement_id
                                               JOIN tbl_user tu ON tpst.user_id = tu.id 
                                               JOIN tbl_semester ts ON ts.id = tpst.semester_id
                                               JOIN tbl_academic_year tay ON tay.id = ts.academic_year_id  
                                               WHERE tpl.isDeleted = 0 
                                                 AND (tev.ePortfolio_grade IS NULL 
                                                 AND tev.internship_grade IS NULL 
                                                 AND tev.final_demo_average IS NULL)
                                                 AND tu.isDeleted = 0 
                                                 AND tu.account_status = 'active' 
                                                 AND tpl.status = 'approved'
                                                 AND tpst.semester_id = ?");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $totalMissingGradeResult = $stmt->get_result();
                    $totalMissingGrade = $totalMissingGradeResult->fetch_assoc()['totalMissingGrade'];
                    $stmt->close();
                    ?>
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Assigned Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users me-2"></i>
                                        <span>Total Pre-Service Teachers Assigned</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Average Evaluation Grade">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        <span>Average Grade</span>
                                    </div>
                                    <h3 class="mb-0"><?= number_format($averageGrade ?? 0.00, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Total ePortfolio Grades Entered">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-alt me-2"></i>
                                        <span>Total Completed Evaluation Grades</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalGrades ?></h3>
                                </div>
                            </div>
                        </div>                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4" role="region" aria-label="Total Missing ePortfolio Grades">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <span>Total Missing Evaluation Grades</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalMissingGrade ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Pre-Service Teachers Evaluation
                        </div>
                        <div class="card-body">
                            <div id="semesterInfo" style="display: none;"><?php echo htmlspecialchars($semesterType); ?></div>
                            <div id="academicYearInfo" style="display: none;"><?php echo htmlspecialchars($academicYearName); ?></div>
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV()" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel()" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Pre-Service Teachers Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Student Number</th>
                                        <th scope="col" role="columnheader">Name</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">e-Portfolio Grade</th>
                                        <th scope="col" role="columnheader">Internship Grade</th>
                                        <th scope="col" role="columnheader">Final Demo Grade</th>
                                        <th scope="col" role="columnheader">Overall Grade</th>
                                        <th scope="col" role="columnheader">Action</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr role="row">
                                        <td role="cell"><?= htmlspecialchars($row['student_number']); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['student_name']); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['program_major']); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['ePortfolio_grade'] ?? ''); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['internship_grade'] ?? ' '); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['final_demo_average'] ?? ''); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['overall_average'] ?? ' '); ?></td>
                                        <td role="cell">
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="openGradeModal(
                                                    <?= $row['pst_id']; ?>,
                                                    <?= $row['placement_id']; ?>,
                                                    <?= $row['student_number']; ?>,
                                                    <?= htmlspecialchars(json_encode($row['student_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['program_major']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= $row['ePortfolio_grade'] !== null ? htmlspecialchars($row['ePortfolio_grade']) : 'null'; ?>,
                                                    <?= $row['internship_grade'] !== null ? htmlspecialchars($row['internship_grade']) : 'null'; ?>,
                                                    <?= htmlspecialchars(json_encode($row['ePortfolio_link']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= $row['final_demo_average'] !== null ? htmlspecialchars($row['final_demo_average']) : 'null'; ?>,
                                                    <?= $row['overall_average'] !== null ? htmlspecialchars($row['overall_average']) : 'null'; ?>
                                                )">
                                                <i class="fa fa-edit"></i></button>
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
    <!-- Edit Grades Modal -->
    <div class="modal fade" id="editGradeModal" tabindex="-1" aria-labelledby="editGradeLabel" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editGradeLabel" role="heading" aria-level="2"><i class="fa fa-edit"></i> Grading Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" role="button"></button>
                </div>
                <div class="modal-body">
                    <form id="gradeForm" enctype="multipart/form-data" onsubmit="return false;" role="form">
                        <input type="hidden" id="pstID" name="pstID">
                        <input type="hidden" id="placementID" name="placementID">
                        <input type="hidden" id="studentNumber" name="studentNumber">
                        <input type="hidden" id="criteriaID" name="criteriaID">

                        <!-- Student Info -->
                        <div class="row mb-3" role="group" aria-labelledby="studentInfoLabel">
                            <div class="col-12 col-md-6">
                                <h6 id="studentInfoLabel" role="heading" aria-level="3"><strong>Student Number:</strong> <span id="studentNumberDisplay"></span></h6>
                            </div>
                        </div>
                        <div class="row mb-3" role="group" aria-labelledby="studentDetailsLabel">
                            <div class="col-12 col-md-6">
                                <h6 id="studentDetailsLabel" role="heading" aria-level="3"><strong>Student Name:</strong> <span id="studentName"></span></h6>
                            </div>
                            <div class="col-12 col-md-6">
                                <h6 role="heading" aria-level="3"><strong>Program - Major:</strong> <span id="programMajor"></span></h6>
                            </div>
                        </div>

                        <!-- Criteria Dropdown (Hidden) -->
                        <div class="row mb-3" style="display: none;">
                            <div class="col-12 col-md-6">
                                <label for="editCriteria" class="form-label">Criteria Percentage:</label>
                                <select class="form-select" id="editCriteria" name="criteria" aria-required="true" onchange="loadCriteriaPercentages()">
                                    <?php
                                    $sqlpct = "SELECT 
                                                   tpct.id,
                                                   tpct.eportfolio_percentage,
                                                   tpct.internship_percentage,
                                                   tpct.final_demo_percentage
                                               FROM tbl_evaluation_criteria_percentage tpct
                                               WHERE tpct.isDeleted = 0 AND tpct.isActive = 1";
                                    $querypct = $conn->prepare($sqlpct);
                                    $querypct->execute();
                                    $result = $querypct->get_result();
                                    while ($criteria = $result->fetch_assoc()) {
                                        echo "<option value=\"{$criteria['id']}\" data-eportfolio=\"{$criteria['eportfolio_percentage']}\" 
                                              data-internship=\"{$criteria['internship_percentage']}\" 
                                              data-final-demo=\"{$criteria['final_demo_percentage']}\">
                                              E-Portfolio: {$criteria['eportfolio_percentage']}%, 
                                              Internship: {$criteria['internship_percentage']}%, 
                                              Final Demo: {$criteria['final_demo_percentage']}%</option>";
                                    }
                                    $querypct->close();
                                    ?>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" id="ePortfolioPercentage" name="ePortfolioPercentage" aria-hidden="true" />
                        <input type="hidden" id="internshipPercentage" name="internshipPercentage" aria-hidden="true" />
                        <input type="hidden" id="finalDemoPercentage" name="finalDemoPercentage" aria-hidden="true" />

                        <div class="table-responsive" role="table" aria-label="Grade Evaluation Table">
                            <table class="table table-bordered">
                                <thead class="table-light" role="rowgroup">
                                    <tr role="row">
                                        <th class="w-25" scope="col">Section</th>
                                        <th scope="col">Grade (out of 100)</th>
                                        <th scope="col">Weighted Grade</th>
                                        <th scope="col">Attachment</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <!-- ePortfolio Section -->
                                    <tr role="row">
                                        <td role="cell" scope="row">
                                            <strong>ePortfolio</strong> (<span id="ePortfolioPercentageDisplay" aria-live="polite"></span>)
                                        </td>
                                        <td role="cell">
                                            <input type="number" class="form-control form-control-sm" id="ePortfolioGrade" name="ePortfolioGrade" step="0.01" max="100" aria-required="true" aria-describedby="ePortfolioGradeHelp" required>
                                            <div id="ePortfolioGradeHelp" class="invalid-feedback">
                                                Please enter a valid ePortfolio grade between 0 and 100.
                                            </div>
                                        </td>
                                        <td role="cell">
                                            <span id="ePortfolioWeightedGrade" aria-live="polite">0.00</span>
                                        </td>
                                        <td role="cell">
                                            <button type="button" class="btn btn-primary btn-sm" id="ePortfolioAttachmentLink" onclick="window.open('#', '_blank');" download aria-label="Download ePortfolio attachment">
                                                <i class="fas fa-paperclip"></i> Download ePortfolio
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Internship Section -->
                                    <tr role="row">
                                        <td role="cell" scope="row">
                                            <strong>Internship</strong> (<span id="internshipPercentageDisplay" aria-live="polite"></span>)
                                        </td>
                                        <td role="cell">
                                            <input type="input" class="form-control form-control-sm" id="internshipGrade" name="internshipGrade" step="0.01" max="100" disabled aria-required="true" required>
                                        </td>
                                        <td role="cell">
                                            <span id="internshipWeightedGrade" aria-live="polite">0.00</span>
                                        </td>
                                        <td role="cell" aria-hidden="true">-</td>
                                    </tr>

                                    <!-- Final Demo Section -->
                                    <tr role="row">
                                        <td role="cell" scope="row">
                                            <strong>Final Demo</strong> (<span id="finalDemoPercentageDisplay" aria-live="polite"></span>)
                                        </td>
                                        <td role="cell">
                                            <input type="number" class="form-control form-control-sm" id="finalDemoAverage" name="finalDemoAverage" step="0.01" readonly>
                                        </td>
                                        <td role="cell">
                                            <span id="finalDemoWeightedGrade" aria-live="polite">0.00</span>
                                        </td>
                                        <td role="cell">
                                            <button type="button" class="btn btn-sm btn-primary" id="addObserverBtn" aria-label="Add Observer Grade">
                                                <i class="fas fa-plus"></i> Add Observer
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Observer Grades Container -->
                                    <tbody id="observerGradesContainer" role="rowgroup"></tbody>
                                </tbody>
                                <tfoot role="rowgroup">
                                    <tr role="row">
                                        <th colspan="2">Overall Average</th>
                                        <td role="cell">
                                            <input type="number" class="form-control form-control-sm" id="overallAverage" name="overallAverage" step="0.01" readonly aria-label="Overall average grade">
                                        </td>
                                        <td role="cell" aria-hidden="true"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close modal">Close</button>
                    <button type="button" class="btn btn-primary" form="gradeForm" onclick="submitGradeForm()" aria-label="Save changes">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel" role="heading" aria-level="2"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" role="button"></button>
                </div>
                <div class="modal-body" id="successModalMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel" role="heading" aria-level="2"><i class="fas fa-times-circle"></i> Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" role="button"></button>
                </div>
                <div class="modal-body" id="errorModalMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast position-fixed top-50 start-50 translate-middle" style="display: none; z-index: 1050;">
        <div class="toast-body bg-success text-white">
            Table copied to clipboard
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script src="../js/xlsx.full.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                const dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No placements have been added for the current active academic year."
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

            const gradeModal = document.getElementById('editGradeModal');
            if (gradeModal) {
                gradeModal.addEventListener('hidden.bs.modal', () => {
                    const ePortfolioGradeField = document.getElementById('ePortfolioGrade');
                    resetValidation(ePortfolioGradeField);
                });

                gradeModal.addEventListener('show.bs.modal', () => {
                    observerCount = 0;
                    document.getElementById('observerGradesContainer').innerHTML = '';

                    // Reset validation for all observer grades
                    const observerGrades = document.querySelectorAll('input[name="observerGrades[]"]');
                    observerGrades.forEach(input => resetValidation(input));
                });
            }

            ['ePortfolioGrade', 'internshipGrade', 'finalDemoAverage'].forEach(id => {
                document.getElementById(id).addEventListener('input', calculateOverallAverage);
            });
        });

        // Modify openGradeModal to trigger calculateOverallAverage after populating modal
        function openGradeModal(pstID, placementID, studentNumber, studentName, programMajor, ePortfolioGrade, internshipGrade, ePortfolioLink, finalDemoAverage, overallAverage) {
            const observerContainer = document.getElementById('observerGradesContainer');
            observerContainer.innerHTML = '';

            document.getElementById('pstID').value = pstID;
            document.getElementById('placementID').value = placementID;
            document.getElementById('studentNumber').value = studentNumber;
            document.getElementById('studentNumberDisplay').textContent = studentNumber;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('programMajor').textContent = programMajor;
            document.getElementById('ePortfolioGrade').value = ePortfolioGrade || ''; 
            document.getElementById('internshipGrade').value = internshipGrade || ''; 
            document.getElementById('finalDemoAverage').value = finalDemoAverage || '';
            document.getElementById('overallAverage').value = overallAverage || '';

            const ePortfolioAttachmentLink = document.getElementById('ePortfolioAttachmentLink');
            if (ePortfolioLink && ePortfolioLink.trim() !== "" && ePortfolioLink.trim() !== "#") {
                ePortfolioAttachmentLink.onclick = () => window.open(`../pre-service-teacher/uploads/e/${ePortfolioLink.trim()}`, '_blank');
                ePortfolioAttachmentLink.disabled = false;
            } else {
                ePortfolioAttachmentLink.onclick = null;
                ePortfolioAttachmentLink.disabled = true;
            }

            loadCriteriaPercentages();
            calculateOverallAverage();

            const modal = new bootstrap.Modal(document.getElementById('editGradeModal'));
            modal.show();
        }

        function calculateOverallAverage() {
            const ePortfolioGrade = parseFloat(document.getElementById('ePortfolioGrade').value) || 0;
            const internshipGrade = parseFloat(document.getElementById('internshipGrade').value) || 0;
            const finalDemoAverage = parseFloat(document.getElementById('finalDemoAverage').value) || 0;
            const ePortfolioPercentage = parseFloat(document.getElementById('ePortfolioPercentage').value) || 0;
            const internshipPercentage = parseFloat(document.getElementById('internshipPercentage').value) || 0;
            const finalDemoPercentage = parseFloat(document.getElementById('finalDemoPercentage').value) || 0;
            const ePortfolioWeighted = (ePortfolioGrade / 100) * (ePortfolioPercentage / 100);
            const internshipWeighted = (internshipGrade / 100) * (internshipPercentage / 100);
            const finalDemoWeighted = (finalDemoAverage / 100) * (finalDemoPercentage / 100);

            document.getElementById('ePortfolioWeightedGrade').innerText = (ePortfolioWeighted * 100).toFixed(2);
            document.getElementById('internshipWeightedGrade').innerText = (internshipWeighted * 100).toFixed(2);
            document.getElementById('finalDemoWeightedGrade').innerText = (finalDemoWeighted * 100).toFixed(2);

            const overallAverage = (ePortfolioWeighted + internshipWeighted + finalDemoWeighted) * 100;

            document.getElementById('overallAverage').value = overallAverage.toFixed(2);
        }

        document.getElementById('ePortfolioGrade').addEventListener('input', calculateOverallAverage);
        document.getElementById('internshipGrade').addEventListener('input', calculateOverallAverage);
        document.getElementById('finalDemoAverage').addEventListener('input', calculateOverallAverage);
        document.getElementById('ePortfolioPercentage').addEventListener('input', calculateOverallAverage);
        document.getElementById('internshipPercentage').addEventListener('input', calculateOverallAverage);
        document.getElementById('finalDemoPercentage').addEventListener('input', calculateOverallAverage);

        calculateOverallAverage();

        function calculateFinalDemoAverage() {
            const observerInputs = document.querySelectorAll('input[name="observerGrades[]"]');
            let total = 0, count = 0;
            
            if (observerInputs.length === 0) {
                document.getElementById('finalDemoAverage').value = 0;
                return;
            }

            observerInputs.forEach(input => {
                const grade = parseFloat(input.value);
                if (!isNaN(grade)) {
                    total += grade;
                    count++;
                }
            });

            const finalDemoAverage = count > 0 ? (total / count).toFixed(2) : 0;
            document.getElementById('finalDemoAverage').value = finalDemoAverage;
            calculateOverallAverage();
        }

        function resetValidation(inputField) {
            inputField.classList.remove('is-invalid');
        }

        function validateGrade(inputField) {
            const grade = parseFloat(inputField.value);
            const isValid = !isNaN(grade) && grade >= 0 && grade <= 100;
            resetValidation(inputField);
            if (!isValid && inputField.value !== '') inputField.classList.add('is-invalid');
            return isValid;
        }

        document.getElementById('ePortfolioGrade').addEventListener('input', function() {
            validateGrade(this);
        });

        function submitGradeForm() {
            const pstID = document.getElementById('pstID').value;
            const placementID = document.getElementById('placementID').value;
            const ePortfolioGradeField = document.getElementById('ePortfolioGrade');
            const ePortfolioGrade = ePortfolioGradeField.value ? parseFloat(ePortfolioGradeField.value) : null;
            const internshipGrade = parseFloat(document.getElementById('internshipGrade').value);
            const overallAverage = parseFloat(document.getElementById('overallAverage').value);
            const ePortfolioLink = document.getElementById('ePortfolioAttachmentLink').onclick ? document.getElementById('ePortfolioAttachmentLink').onclick.toString() : null;
            // const ePortfolioLink = document.getElementById('ePortfolioAttachmentLink').value || null;

            const criteriaID = document.getElementById('criteriaID').value;

            resetValidation(ePortfolioGradeField);
            if (ePortfolioGrade !== null && (!ePortfolioLink || ePortfolioLink.includes('null'))) {
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editGradeModal'));
                editModal.hide();

                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                document.getElementById('errorModalMessage').textContent = 'No ePortfolio link is available. Cannot submit ePortfolio grade.';
                errorModal.show();
                return;
            }

            const observerGrades = document.querySelectorAll('input[name="observerGrades[]"]');
            const hasObserverGrades = Array.from(observerGrades).some(input => input.value.trim() !== "");

            if (hasObserverGrades) {
                calculateFinalDemoAverage();
            }

            const calculatedFinalDemoAverage = parseFloat(document.getElementById('finalDemoAverage').value);

            const formData = new FormData();
            formData.append('pstID', pstID);
            formData.append('placementID', placementID);
            formData.append('ePortfolioGrade', (ePortfolioGrade !== null && !isNaN(ePortfolioGrade)) ? ePortfolioGrade : null);
            formData.append('internshipGrade', internshipGrade);
            formData.append('finalDemoAverage', calculatedFinalDemoAverage);
            formData.append('overallAverage', overallAverage);
            formData.append('criteriaID', criteriaID);

            const observerAttachments = document.querySelectorAll('input[name="observerAttachments[]"]');

            observerGrades.forEach((input, index) => {
                const grade = parseFloat(input.value);
                formData.append(`observerGrades[${index}]`, isNaN(grade) ? '' : grade);
            });

            observerAttachments.forEach((input, index) => {
                if (input.files[0]) {
                    formData.append(`observerAttachments[${index}]`, input.files[0]);
                }
            });

            fetch('functions/update-grades.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Server Response:', text);
                const data = JSON.parse(text);
                if (data.success) {
                    // Show the success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    document.getElementById('successModalMessage').textContent = data.message || 'Grades updated successfully.';
                    successModal.show();
                    successModal._element.addEventListener('hidden.bs.modal', function() {
                        window.location.reload();
                    });

                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editGradeModal'));
                    editModal.hide();
                } else {
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editGradeModal'));
                    editModal.hide();

                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    document.getElementById('errorModalMessage').textContent = data.message || 'An error occurred while updating the grades.';
                    errorModal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);

                const editModal = bootstrap.Modal.getInstance(document.getElementById('editGradeModal'));
                editModal.hide();

                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                document.getElementById('errorModalMessage').textContent = 'An error occurred while submitting the form.';
                errorModal.show();
            });
        }

        let observerCount = 0;

        function addObserverRow(grade = '', attachmentLink = '') {
            observerCount++;
            const observerContainer = document.getElementById('observerGradesContainer');
            const observerRow = document.createElement('tr');
            observerRow.setAttribute('id', `observerRow${observerCount}`);

            observerRow.innerHTML = `
                <td role="cell"></td>
                <td role="cell">
                    <label for="observer${observerCount}Grade" class="form-label">Observer ${observerCount}</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-sm" id="observer${observerCount}Grade" name="observerGrades[]" value="${grade}" step="0.01" max="100" required>
                        <div class="invalid-feedback">
                            Please enter a valid observer grade between 0 and 100.
                        </div>
                    </div>
                </td>
                <td role="cell">
                    <label for="observer${observerCount}Attachment" class="form-label">Upload</label>
                    <div class="input-group">
                        <input type="file" class="form-control form-control-sm" id="observer${observerCount}Attachment" name="observerAttachments[]" accept=".csv, .pdf, .jpg, .jpeg, .png, .gif, .bmp, .docx">
                        ${attachmentLink ? `<a href="${attachmentLink}" target="_blank" class="btn btn-link btn-sm">View</a>` : ''}
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeObserver(${observerCount})" style="padding: 0 0.5rem;">&times;</button>
                    </div>
                </td>
                <td role="cell">
                </td>
            `;
            observerContainer.appendChild(observerRow);

            const observerGradeInput = document.getElementById(`observer${observerCount}Grade`);
            observerGradeInput.addEventListener('input', function () {
                validateGrade(this);
                calculateFinalDemoAverage();
            });
        }

        function removeObserver(observerIndex) {
            const observerRow = document.getElementById(`observerRow${observerIndex}`);
            if (observerRow) {
                observerRow.remove();
                observerCount--;
            }
            const remainingRows = document.querySelectorAll('#observerGradesContainer tr');
            let newIndex = 1;
            remainingRows.forEach((row) => {
                const label = row.querySelector('label');
                if (label) {
                    label.textContent = `Observer ${newIndex}`;
                    const gradeInput = row.querySelector('input[name="observerGrades[]"]');
                    if (gradeInput) gradeInput.id = `observer${newIndex}Grade`;
                    const attachmentInput = row.querySelector('input[name="observerAttachments[]"]');
                    if (attachmentInput) attachmentInput.id = `observer${newIndex}Attachment`;
                    newIndex++;
                }
            });

            calculateFinalDemoAverage();
        }

        document.getElementById('addObserverBtn').addEventListener('click', () => addObserverRow());

        function loadCriteriaPercentages() {
            const selectedOption = document.getElementById('editCriteria').selectedOptions[0];
            const criteriaID = selectedOption.value;

            document.getElementById('criteriaID').value = criteriaID;

            // Fetch and set percentage values as before
            const ePortfolioPercentage = parseFloat(selectedOption.getAttribute('data-eportfolio')) || 0;
            const internshipPercentage = parseFloat(selectedOption.getAttribute('data-internship')) || 0;
            const finalDemoPercentage = parseFloat(selectedOption.getAttribute('data-final-demo')) || 0;

            document.getElementById('ePortfolioPercentage').value = ePortfolioPercentage;
            document.getElementById('internshipPercentage').value = internshipPercentage;
            document.getElementById('finalDemoPercentage').value = finalDemoPercentage;
            document.getElementById('ePortfolioPercentageDisplay').innerText = `${ePortfolioPercentage}%`;
            document.getElementById('internshipPercentageDisplay').innerText = `${internshipPercentage}%`;
            document.getElementById('finalDemoPercentageDisplay').innerText = `${finalDemoPercentage}%`;
        }

        function copyTable() {
            const table = document.getElementById('datatablesSimple');

            if (!table) return;

            const headers = Array.from(table.querySelectorAll('thead th:not(:last-child)'))
                .map(th => th.innerText.trim())
                .join('\t'); // Join headers with tabs

            const rows = table.querySelectorAll('tbody tr');
            const copiedRows = [];

            rows.forEach(row => {
                const cells = row.querySelectorAll('td:not(:last-child)'); // Exclude action column
                const rowText = Array.from(cells).map(cell => cell.innerText.trim()).join('\t');
                copiedRows.push(rowText);
            });

            const tableText = headers + '\n' + copiedRows.join('\n'); // Add headers at the top

            // Use Clipboard API to copy text
            navigator.clipboard.writeText(tableText).then(() => {
                // Show the toast notification
                const toast = document.getElementById('toast');
                toast.style.display = 'block';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 2000);  // Hide after 2 seconds
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        function exportTableToCSV() {
            // Get the semester and academic year from hidden divs
            const semesterType = document.getElementById('semesterInfo').innerText.trim().toUpperCase();
            const academicYearName = document.getElementById('academicYearInfo').innerText.trim();

            // Construct the filename dynamically, check for null or empty semesterType and academicYearName
            let filename = 'List of Pre-Service Teacher Evaluation';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Student Number", "Pre-Service Teacher Name", "Program-Major", "e-Portfolio", "Internship", "Final Demo", "Overall Grade"];
            csv.push(headers.map(header => `"${header}"`).join(',')); 

            // Get table rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('td:not(:last-child)'); // Exclude action column
                let rowData = [];

                cols.forEach((col, index) => {
                    let text = col.innerText.trim();

                    // Ensure proper handling of special characters (e.g., Ñ)
                    text = text.normalize("NFC");

                    // Wrap values with commas inside double quotes to prevent CSV misformatting
                    if (text.includes(',') || text.includes('"')) {
                        text = `"${text.replace(/"/g, '""')}"`; // Escape double quotes
                    } else {
                        // Always wrap text in double quotes, even if it doesn't contain special characters
                        text = `"${text}"`;
                    }

                    // Remove multiple spaces
                    if (index === 0) {
                        text = text.replace(/\s+/g, ' ');
                    }

                    rowData.push(text);
                });

                csv.push(rowData.join(','));
            });

            // Call your existing download function
            downloadCSV(csv.join('\n'), filename);
        }

        function downloadCSV(csv, filename) {
            const BOM = '\uFEFF'; // Add UTF-8 BOM to fix special character encoding
            const csvFile = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function exportTableToExcel() {
            // Get the semester and academic year from hidden divs
            const semesterType = document.getElementById('semesterInfo').innerText.trim().toUpperCase();
            const academicYearName = document.getElementById('academicYearInfo').innerText.trim();

            // Construct the filename dynamically, check for null or empty semesterType and academicYearName
            let filename = 'List of Pre-Service Teacher Evaluation';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.xlsx`;
            } else {
                filename += '.xlsx';
            }

            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tbody tr');
            const headers = Array.from(table.querySelectorAll('thead th')).slice(0, -1).map(th => th.innerText.trim());
            const data = [];

            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td:not(:last-child)');
                cells.forEach(cell => {
                    rowData.push(cell.innerText.trim());
                });
                data.push(rowData);
            });

            const wsData = [headers];
            data.forEach(row => {
                wsData.push(row);
            });

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
            XLSX.writeFile(wb, filename);
        }

        function printTable() {
            const table = document.getElementById('datatablesSimple').cloneNode(true);
            const currentDate = new Date().toLocaleString();

            let win = window.open('', '_blank');
            if (!win) {
                alert("Pop-up blocked! Please allow pop-ups for this site.");
                return;
            }
            win.document.write('<html><head><title>Pre-Service Teacher Evaluation Grade</title>');
            win.document.write(`
                <style>
                    @page {
                        size: A4 portrait;
                        margin-top: 5.7mm;   /* 0.38 inches */
                        margin-bottom: 7.9mm; /* 0.31 inches */
                        margin-left: 25.4mm;  /* 1 inch */
                        margin-right: 25.4mm; /* 1 inch */
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 11pt;
                        text-align: center;
                        margin: 0;
                        padding: 0;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                    }
                    th, td { 
                        padding: 6px; 
                        text-align: left; 
                        border-bottom: 1px solid #ddd; 
                        font-size: 11pt;
                        word-wrap: break-word;
                    }
                    th { 
                        background-color: #f2f2f2; 
                    }
                    .header-container { 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        text-align: center; 
                        margin-bottom: 10px; 
                    }
                    .header-container img { 
                        width: 100px; 
                        height: auto; 
                        margin-right: 15px; 
                    }
                    .text-container { 
                        display: flex; 
                        flex-direction: column; 
                        align-items: center; 
                    }
                    .header-container p { 
                        margin: 0; 
                        line-height: 1.40; 
                    }
                    .gov-text { font-family: "Century Gothic", sans-serif; font-size: 11pt; }
                    .univ-text { font-family: "Bookman Old Style", serif; font-size: 14pt; font-weight: bold; }
                    .campus-text { font-family: "Century Gothic", sans-serif; font-size: 11pt; font-weight: bold; }
                    .location-text { font-family: "Century Gothic", sans-serif; font-size: 10pt; }
                    .college-text { font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; text-align: center; }
                    .title-text { font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; text-align: center; }
                </style>
            `);
            win.document.write('</head><body>');
            win.document.write(`
                <div class="header-container" style="margin-bottom: 5px; margin-left: -115px">
                    <img id="cvsuLogo" src="../assets/img/cvsu-logo-header.jpg" alt="CVSU Logo" style="margin-bottom: 30px">
                    <div class="text-container">
                        <p class="gov-text">Republic of the Philippines</p>
                        <p class="univ-text">CAVITE STATE UNIVERSITY</p>
                        <p class="campus-text" style="margin-bottom: 5px">Don Severino de las Alas Campus</p>
                        <p class="location-text">Indang, Cavite</p>
                    </div>
                </div>
            `);
            win.document.write(`
                <div class="header-container" style="margin-bottom: 15px; justify-content: center;">
                    <p class="college-text">COLLEGE OF EDUCATION</p>
                </div>
            `);

            win.document.write('<div class="header-container"><p class="title-text">List of Pre-Service Teacher Evaluation Grade</p></div>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Student Number</th><th>Name</th><th>Program-Major</th><th>e-Portfolio Grade</th><th>Internship Grade</th><th>Final Demo Grade</th><th>Overall Grade</th></tr></thead>');
            win.document.write('<tbody>');

            table.querySelectorAll('tbody tr').forEach(row => {
                let cells = Array.from(row.cells);
                win.document.write('<tr>' + cells.map(cell => `<td>${cell.innerText}</td>`).join('') + '</tr>');
            });

            win.document.write('</tbody></table>');
            win.document.write(`
                <div style="margin-top: 20px; text-align: right; font-size: 10pt; font-style: italic;">
                    Date and Time Generated: ${currentDate}
                </div>
            `);

            win.document.write(`
                <script>
                    window.onload = function() {
                        setTimeout(() => {
                            window.print();
                        }, 500);
                    };
                <\/script>
            `);

            win.document.write('</body></html>');
            win.document.close();
        }
    </script>
    </body>
</html>
