<?php 
include 'includes/auth.php';

// Fetch the Cooperating Teacher ID based on the logged-in user's ID
$sqlCTID = "SELECT id FROM tbl_cooperating_teacher WHERE user_id = ?";
$stmt = $conn->prepare($sqlCTID);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ct = $result->fetch_assoc();
$ct_id = $ct['id'];

$academicYearName = '';
$semesterType = '';

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
    $semesterType = $semesterRow['type'];

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

    // Modify the SQL query to fetch only pre-service teachers assigned to this cooperating teacher
    $stmt = $conn->prepare("SELECT 
        tpst.id AS pst_id,
        tpst.student_number,
        CONCAT(tu.last_name, ', ', tu.first_name, 
        CASE 
            WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
            ELSE ''
        END) AS student_name,
        tu.email, 
        tay.academic_year_name, 
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
        CONCAT(tu.street, ' ', tu.barangay, ' ', tu.city_municipality, ' ', tu.province) AS address,
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
        tpl.id AS placement_id,
        tpl.start_date,
        tpl.end_date,
        tev.internship_grade
    FROM 
        tbl_placement tpl
    JOIN 
        tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
    JOIN 
        tbl_user tu ON tpst.user_id = tu.id
    JOIN 
        tbl_school tcs ON tpl.school_id = tcs.id
    JOIN
        tbl_semester ts ON tpst.semester_id = ts.id
    JOIN
        tbl_academic_year tay ON ts.academic_year_id = tay.id
    LEFT JOIN 
        tbl_adviser_assignment aa ON aa.placement_id = tpl.id
    LEFT JOIN 
        tbl_adviser ta ON ta.id = aa.adviser_id
    LEFT JOIN 
        tbl_user tadvu ON ta.user_id = tadvu.id
    LEFT JOIN 
        tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
    LEFT JOIN 
        tbl_cooperating_teacher tct ON tct.id = cta.cooperating_teacher_id
    LEFT JOIN 
        tbl_user tctu ON tctu.id = tct.user_id
    LEFT JOIN
        tbl_evaluation tev ON tpl.id = tev.placement_id
    JOIN 
        tbl_program tp ON tpst.program_id = tp.id
    LEFT JOIN 
        tbl_major tm ON tpst.major_id = tm.id
    WHERE 
        tpl.isDeleted = 0
        AND tpl.status = 'approved'
        AND tpst.placement_status = 'placed'
        AND tu.isDeleted = 0
        AND cta.cooperating_teacher_id = ?");

    $stmt->bind_param("i", $ct_id);  // Bind the cooperating teacher's ID
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
    <title>Internship Evaluation - Cooperating Teacher</title>
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
                    <h1 class="mt-5 h3" id="main-heading">Internship Evaluation</h1>
                    <ol class="breadcrumb mb_4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Internship Evaluation</li>
                    </ol>

                    <?php
                    $totalAssignedQuery = "SELECT COUNT(*) as totalAssigned FROM tbl_placement tpl
                                             JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                                             WHERE cta.cooperating_teacher_id = ? AND tpl.isDeleted = 0";
                    $totalAssignedStmt = $conn->prepare($totalAssignedQuery);
                    $totalAssignedStmt->bind_param("i", $ct_id);
                    $totalAssignedStmt->execute();
                    $totalAssignedResult = $totalAssignedStmt->get_result();
                    $totalAssigned = $totalAssignedResult->fetch_assoc()['totalAssigned'];

                    $totalGradesQuery = "SELECT COUNT(*) as totalGrades FROM tbl_evaluation tev
                                          JOIN tbl_placement tpl ON tev.placement_id = tpl.id
                                          JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                                          WHERE tpl.isDeleted = 0 AND tev.internship_grade IS NOT NULL
                                          AND cta.cooperating_teacher_id = ?";
                    $totalGradesStmt = $conn->prepare($totalGradesQuery);
                    $totalGradesStmt->bind_param("i", $ct_id);
                    $totalGradesStmt->execute();
                    $totalGradesResult = $totalGradesStmt->get_result();
                    $totalGrades = $totalGradesResult->fetch_assoc()['totalGrades'];

                    $averageGradeQuery = "SELECT AVG(tev.internship_grade) as avgGrade FROM tbl_evaluation tev
                                           JOIN tbl_placement tpl ON tev.placement_id = tpl.id
                                           JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                                           WHERE tpl.isDeleted = 0 AND tev.internship_grade IS NOT NULL
                                           AND cta.cooperating_teacher_id = ?";
                    $averageGradeStmt = $conn->prepare($averageGradeQuery);
                    $averageGradeStmt->bind_param("i", $ct_id);
                    $averageGradeStmt->execute();
                    $averageGradeResult = $averageGradeStmt->get_result();
                    $averageGrade = $averageGradeResult->fetch_assoc()['avgGrade'];

                    $totalMissingGradeQuery = "SELECT COUNT(*) as totalMissingGrade
                                                 FROM tbl_evaluation tev
                                                 JOIN tbl_placement tpl ON tev.placement_id = tpl.id
                                                 JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                                                 WHERE tpl.isDeleted = 0 AND tev.internship_grade IS NULL
                                                 AND cta.cooperating_teacher_id = ?";
                                                 
                    $totalMissingGradeStmt = $conn->prepare($totalMissingGradeQuery);
                    $totalMissingGradeStmt->bind_param("i", $ct_id);
                    $totalMissingGradeStmt->execute();
                    $totalMissingGradeResult = $totalMissingGradeStmt->get_result();
                    $totalMissingGrade = $totalMissingGradeResult->fetch_assoc()['totalMissingGrade'];
                    ?>
                    <div class="row">
                        <!-- Total Assigned Pre-Service Teachers -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Assigned Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users me-2"></i>
                                        <span>Total Pre-Service Teachers Assigned</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalAssigned ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Average Internship Grade -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Average Internship Grade">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        <span>Average Internship Grade</span>
                                    </div>
                                    <h3 class="mb-0"><?= number_format($averageGrade ?? 0.00, 2) ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Total Internship Grades Entered -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Total Internship Grades Entered">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-alt me-2"></i>
                                        <span>Total Internship Grades Entered</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalGrades ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Total Missing Internship Grades -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4" role="region" aria-label="Total Unentered Grade">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <span>Total Missing Internship Grades</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalMissingGrade ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                You can grade internship performance here.
                            </p>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Pre-Service Teachers
                        </div>
                        <div class="card-body">
                            <div id="semesterInfo" style="display: none;"><?php echo htmlspecialchars($semesterType); ?></div>
                            <div id="academicYearInfo" style="display: none;"><?php echo htmlspecialchars($academicYearName); ?></div>
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary" role="button">Copy</button>
                                    <button onclick="exportTableToCSV()" class="btn btn-secondary" role="button">CSV</button>
                                    <button onclick="exportTableToExcel()" class="btn btn-secondary" role="button">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary" role="button">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Pre-Service Teachers Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Pre-Service Teacher</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Internship Grade</th>
                                        <th scope="col" role="columnheader">Action</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr role="row">
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['program_major']); ?></td>
                                        <td><?php echo htmlspecialchars($row['internship_grade'] ?? 0); ?></td>
                                        <td role="cell">
                                            <button type="button" class="btn btn-primary btn-sm" 
                                                onclick="openGradeModal(
                                                    <?php echo htmlspecialchars($row['pst_id']); ?>,
                                                    <?php echo htmlspecialchars($row['placement_id']); ?>,
                                                    <?php echo htmlspecialchars($row['student_number']); ?>,
                                                    <?php echo htmlspecialchars(json_encode($row['student_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?php echo htmlspecialchars(json_encode($row['program_major']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?php echo htmlspecialchars(json_encode(date('F j, Y', strtotime($row['start_date']))), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?php echo htmlspecialchars(json_encode(date('F j, Y', strtotime($row['end_date']))), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?php echo htmlspecialchars($row['internship_grade'] ?? 0); ?>
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

    <!-- Edit Grades Modal (Grading Sheet Format) -->
    <div class="modal fade" id="editGradeModal" tabindex="-1" aria-labelledby="editGradeLabel" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editGradeLabel" role="heading" aria-level="2"><i class="fa fa-edit"></i> Grading Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" role="button"></button>
                </div>
                <div class="modal-body">
                    <form id="gradeForm" enctype="multipart/form-data" onsubmit="return false;" role="form">
                        <input type="hidden" id="pstID" name="pstID">
                        <input type="hidden" id="placementID" name="placementID">
                        <input type="hidden" id="studentNumber" name="studentNumber">

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
                        <div class="row mb-3" role="group" aria-labelledby="dateInfoLabel">
                            <div class="col-md-6">
                                <h6 id="dateInfoLabel" role="heading" aria-level="3"><strong>Start Date:</strong> <span id="editStartDate"></span></h6>
                            </div>
                            <div class="col-md-6">
                                <h6 role="heading" aria-level="3"><strong>End Date:</strong> <span id="editEndDate"></span></h6>
                            </div>
                        </div>

                        <div class="table-responsive" role="table" aria-label="Grading Information">
                            <table class="table table-bordered" role="table" aria-label="Grading Information Table">
                                <thead class="table-light" role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Section</th>
                                        <th scope="col" role="columnheader">Grade (out of 100)</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <tr role="row">
                                        <td role="cell">
                                            <strong>Internship</strong>
                                            <p class="small text-muted">Evaluation based on performance and contributions during the internship.</p>
                                        </td>
                                        <td role="cell">
                                            <input type="number" class="form-control form-control-sm" id="internshipGrade" name="internshipGrade" step="0.01" max="100" required aria-describedby="internshipGradeDesc" aria-labelledby="internshipGradeLabel">
                                            <div id="internshipGradeDesc" class="invalid-feedback">
                                                Please enter a valid internship grade between 0 and 100.
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" role="button">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitGradeForm()" role="button">Save changes</button>
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
                <div class="modal-body">
                    <p id="successModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" role="button">OK</button>
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
                <div class="modal-body">
                    <p id="errorModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" role="button">OK</button>
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
                        noRows: "No pre-service teachers have been assigned to you yet."
                    }
                });
            }
            const gradeModal = document.getElementById('editGradeModal');
            if (gradeModal) {
                gradeModal.addEventListener('hidden.bs.modal', () => {
                    const internshipGradeField = document.getElementById('internshipGrade');
                    resetValidation(internshipGradeField);
                });
            }
        });

        function openGradeModal(pstID, placementID, studentNumber, studentName, programMajor, startDate, endDate, internshipGrade) {
            document.getElementById('pstID').value = pstID;
            document.getElementById('placementID').value = placementID;
            document.getElementById('studentNumber').value = studentNumber;
            document.getElementById('studentNumberDisplay').textContent = studentNumber;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('programMajor').textContent = programMajor;
            document.getElementById('editStartDate').textContent = startDate;
            document.getElementById('editEndDate').textContent = endDate;
            document.getElementById('internshipGrade').value = internshipGrade || '';

            const modal = new bootstrap.Modal(document.getElementById('editGradeModal'));
            modal.show();
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

        document.getElementById('internshipGrade').addEventListener('input', function() {
            validateGrade(this);
        });

        function submitGradeForm() {
            const pstID = document.getElementById('pstID').value;
            const placementID = document.getElementById('placementID').value;
            const internshipGradeField = document.getElementById('internshipGrade');
            const internshipGrade = internshipGradeField.value ? parseFloat(internshipGradeField.value) : null;

            const formData = new FormData();
            formData.append('pstID', pstID);
            formData.append('placementID', placementID);
            formData.append('internshipGrade', (internshipGrade !== null && !isNaN(internshipGrade)) ? internshipGrade : null);

            fetch('functions/update-grades.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
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
            let filename = 'List of Pre-Service Teachers with Internship Grade';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Pre-Service Teacher Name", "Program-Major", "Internship Grade"];
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
            let filename = 'List of Pre-Service Teachers with Internship Grade';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.xlsx`;
            } else {
                filename += '.xlsx';
            }

            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tbody tr');
            const headers = Array.from(table.querySelectorAll('thead th')).slice(0, -1).map(th => th.innerText.trim());  // Exclude last header
            const data = [];

            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                Array.from(cells).slice(0, -1).forEach(cell => {  // Exclude the last cell
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

            table.querySelectorAll('thead th')[3].remove();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.cells[3].remove();
            });
            table.querySelector('thead').remove();

            const currentDate = new Date().toLocaleString();

            const win = window.open('', '_blank');
            win.document.write('<html><head><title>Pre-Service Teachers</title>');
            win.document.write('<style>table {width: 100%; border-collapse: collapse;} th, td {padding: 8px; text-align: left; border-bottom: 1px solid #ddd; font-size: 15px;} th {background-color: #f2f2f2;} body {font-family: Arial, sans-serif; font-size: 15px;} .header {margin-bottom: 10px; font-size: 12px; }</style>');
            win.document.write('</head><body>');
            win.document.write('<div class="header">Date and Time Generated: ' + currentDate + '</div>');
            win.document.write('<h2 style="margin-top: 0;">List of Pre-Service Teachers</h2>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Student Name</th><th>Program - Major</th><th>Internship Grade</th></tr></thead><tbody>');

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                win.document.write('<tr>');
                cells.forEach(cell => {
                    win.document.write('<td>' + cell.innerText.trim() + '</td>');
                });
                win.document.write('</tr>');
            });

            win.document.write('</tbody></table>');
            win.document.write('</body></html>');

            win.document.close();
            win.print();
        }
    </script>
</body>
</html>