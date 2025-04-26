<?php 
header("Content-Security-Policy: default-src 'self'; " . 
    "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; " . 
    "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " . 
    "img-src 'self' data:; " . 
    "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " . 
    "connect-src 'self';");

include 'includes/auth.php';

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

    $stmt = $conn->prepare("SELECT 
            tpl.id AS placement_id,
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
            tpst.program_id, 
            tpst.major_id, 
            tpl.school_id, 
            tpl.created_at, 
            ts.school_name, 
            taa.adviser_id AS adviser_id,
            ta.id, 
            TRIM(
                CONCAT(
                    tadv.first_name, 
                    CASE 
                        WHEN tadv.middle_name IS NOT NULL AND tadv.middle_name != '' THEN CONCAT(' ', tadv.middle_name) 
                        ELSE '' 
                    END, 
                    ' ', 
                    tadv.last_name
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
            taa.date_assigned
        FROM 
            tbl_pre_service_teacher tpst
        INNER JOIN 
            tbl_user tu ON tpst.user_id = tu.id
        INNER JOIN 
            tbl_program tp ON tpst.program_id = tp.id
        LEFT JOIN
            tbl_major tm ON tpst.major_id = tm.id
        INNER JOIN 
            tbl_placement tpl ON tpst.id = tpl.pre_service_teacher_id
        INNER JOIN 
            tbl_school ts ON tpl.school_id = ts.id
        LEFT JOIN 
            tbl_cooperating_teacher_assignment tcta ON tcta.placement_id = tpl.id
        LEFT JOIN 
            tbl_cooperating_teacher tct ON tcta.cooperating_teacher_id = tct.id
        LEFT JOIN 
            tbl_adviser_assignment taa ON taa.placement_id = tpl.id
        LEFT JOIN 
            tbl_adviser ta ON taa.adviser_id = ta.id
        LEFT JOIN 
            tbl_user tctu ON tct.user_id = tctu.id
        LEFT JOIN 
            tbl_user tadv ON ta.user_id = tadv.id
        WHERE
            tu.isDeleted = 0
            AND tu.account_status = 'active'
            AND tpst.placement_status != 'unplaced'
            AND tpl.status != 'rejected'
            AND tpl.isDeleted = 0
            AND tpst.semester_id = ?
        ORDER BY
            (CASE 
                WHEN taa.adviser_id IS NULL AND tct.id IS NULL THEN 1
                WHEN taa.adviser_id IS NULL AND tct.id IS NOT NULL THEN 2
                WHEN taa.adviser_id IS NOT NULL AND tct.id IS NULL THEN 3
                ELSE 4
            END),
            tu.last_name ASC,
            tpl.created_at DESC,
            tpl.approved_by ASC");

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
    <title>Assign Adviser - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
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
                    <h1 class="mt-5 h3" id="main-heading">Assign Adviser</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Assign Adviser</li>
                    </ol>
                    <?php
                    // Query to count total placements with both adviser and cooperating teacher assigned
                    $totalBothQuery = "
                        SELECT COUNT(*) AS total_placements 
                        FROM tbl_placement tpl
                        JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                        JOIN tbl_adviser_assignment aa ON tpl.id = aa.placement_id
                        JOIN tbl_pre_service_teacher pst ON tpl.pre_service_teacher_id = pst.id
                        JOIN tbl_user u ON pst.user_id = u.id
                        WHERE tpl.isDeleted = 0 AND pst.placement_status = 'placed' AND tpl.status = 'approved'";
                    $stmtBoth = $conn->prepare($totalBothQuery);
                    $stmtBoth->execute();
                    $totalBothResult = $stmtBoth->get_result();
                    $totalBoth = $totalBothResult->fetch_assoc()['total_placements'];

                    // Query to count placements with only an adviser assigned, but no cooperating teacher
                    $totalAdviserOnlyQuery = "
                        SELECT COUNT(*) AS total_placements
                        FROM tbl_placement tpl
                        JOIN tbl_adviser_assignment aa ON tpl.id = aa.placement_id
                        LEFT JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                        JOIN tbl_pre_service_teacher pst ON tpl.pre_service_teacher_id = pst.id
                        JOIN tbl_user u ON pst.user_id = u.id
                        WHERE tpl.status = 'approved' AND pst.placement_status = 'placed' AND cta.placement_id IS NULL AND cta.placement_id IS NULL AND tpl.isDeleted = 0";
                    $stmtAdviserOnly = $conn->prepare($totalAdviserOnlyQuery);
                    $stmtAdviserOnly->execute();
                    $totalAdviserOnlyResult = $stmtAdviserOnly->get_result();
                    $totalAdviserOnly = $totalAdviserOnlyResult->fetch_assoc()['total_placements'];

                    // Query to count placements with no cooperating teacher or adviser assigned
                    $totalNoneQuery = "
                        SELECT COUNT(*) AS total_placements
                        FROM tbl_placement tpl
                        LEFT JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                        LEFT JOIN tbl_adviser_assignment aa ON tpl.id = aa.placement_id
                        JOIN tbl_pre_service_teacher pst ON tpl.pre_service_teacher_id = pst.id
                        JOIN tbl_user u ON pst.user_id = u.id
                        WHERE tpl.status = 'approved' AND pst.placement_status = 'placed' AND cta.placement_id IS NULL AND aa.placement_id IS NULL AND tpl.isDeleted = 0";
                    $stmtNone = $conn->prepare($totalNoneQuery);
                    $stmtNone->execute();
                    $totalNoneResult = $stmtNone->get_result();
                    $totalNone = $totalNoneResult->fetch_assoc()['total_placements'];
                    ?>

                    <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Both Adviser and Cooperating Teacher">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie me-2"></i>
                                        <span>Both Adviser & Cooperating Teacher</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalBoth ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Only Adviser">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-cog me-2"></i>
                                        <span>Adviser Only</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalAdviserOnly ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Neither Adviser nor Cooperating Teacher">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users-slash me-2"></i>
                                        <span>Neither Adviser nor Cooperating Teacher</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalNone ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Card -->
                    <div class="card mb-4">
                        <div class="card-body" role="note">
                            <p class="mb-0">Assign an adviser to each approved pre-service teacher placement.</p>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Approved Pre-Service Teacher Placement
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
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Adviser and Cooperating Teacher Assignment Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Student Number</th>
                                        <th scope="col" role="columnheader">Pre-Service Teacher Name</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Placement School</th>
                                        <th scope="col" role="columnheader">Adviser</th>
                                        <th scope="col" role="columnheader">Cooperating Teacher</th>
                                        <th scope="col" role="columnheader">Action</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr role="row">
                                        <td class="text-center" role="cell"><?php echo htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?php echo htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?php echo htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?php echo htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                                        <td role="cell">
                                            <button type="button" class="btn btn-warning btn-sm btn-action" 
                                                onclick="openEditModal(
                                                    <?php echo htmlspecialchars($row['placement_id'], ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?php echo htmlspecialchars(json_encode($row['student_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?php echo htmlspecialchars(json_encode($row['program_major']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?php echo htmlspecialchars(json_encode($row['school_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?php echo isset($row['adviser_id']) ? htmlspecialchars($row['adviser_id'], ENT_QUOTES, 'UTF-8') : 'null'; ?>
                                                )">
                                                <i class="fa fa-edit"></i>
                                            </button>
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

    <!-- Assign Adviser Modal -->
    <div class="modal fade" id="editAModal" role="dialog" tabindex="-1" aria-labelledby="assignAdviserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="assignAdviserModalLabel"><i class="fa fa-edit"></i> Assign Adviser</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST" action="functions/process-adviser-assignment.php">
                <div class="modal-body">
                    <input type="hidden" id="modalStudentId" name="placement_id" />
                    <p><strong>Student Name:</strong> <span id="modalStudentName"></span></p>
                    <p><strong>Program - Major:</strong> <span id="modalProgramMajor"></span></p>
                    <p><strong>School Placed:</strong> <span id="modalSchoolPlaced"></span></p>
                    <div class="mb-3">
                        <label for="aSelect" class="form-label">Adviser:</label>
                        <select id="aSelect" name="adviser_id" required class="form-select">
                            <?php
                            $assigned_adviser_id = null;
                            $placement_id = $row['placement_id'];

                            $assigned_adviser_query = "
                                SELECT adviser_id 
                                FROM tbl_adviser_assignment 
                                WHERE placement_id = ? 
                                LIMIT 1";

                            $stmt_assigned_adviser = $conn->prepare($assigned_adviser_query);
                            $stmt_assigned_adviser->bind_param("i", $placement_id);
                            $stmt_assigned_adviser->execute();
                            $result_assigned_adviser = $stmt_assigned_adviser->get_result();

                            if ($result_assigned_adviser->num_rows > 0) {
                                $assigned_adviser_row = $result_assigned_adviser->fetch_assoc();
                                $assigned_adviser_id = $assigned_adviser_row['adviser_id'];
                            }

                            echo '<option value="" disabled ' . (is_null($assigned_adviser_id) ? 'selected' : '') . '>Select Adviser</option>';

                            $adviser_query = "
                                SELECT
                                    ta.id, 
                                    TRIM(
                                        CONCAT(
                                            tu.first_name, 
                                            CASE 
                                                WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name) 
                                                ELSE '' 
                                            END, 
                                            ' ', 
                                            tu.last_name
                                        )
                                    ) AS adviser_name
                                FROM tbl_adviser ta
                                INNER JOIN tbl_user tu ON ta.user_id = tu.id
                                WHERE tu.account_status = 'active' AND tu.isDeleted = 0";

                            $stmt_adviser = $conn->prepare($adviser_query);
                            $stmt_adviser->execute();
                            $adviser_result = $stmt_adviser->get_result();

                            while ($adviser_row = $adviser_result->fetch_assoc()) {
                                $selected = ($adviser_row['id'] == $assigned_adviser_id) ? 'selected' : ''; // Check if this adviser is the assigned one
                                echo "<option value=\"{$adviser_row['id']}\" $selected>" . htmlspecialchars($adviser_row['adviser_name'], ENT_QUOTES, 'UTF-8') . "</option>";
                            }

                            if ($adviser_result->num_rows == 0) {
                                echo "<option value='' disabled>No advisers available</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" role="dialog" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" role="dialog" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel"><i class="fas fa-times-circle"></i> Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

            // Form handling
            const editForm = document.getElementById('editForm');
            editForm.onsubmit = function (e) {
                e.preventDefault();

                const formData = new FormData(this);

                // Use fetch API to send the request
                fetch('functions/process-adviser-assignment.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    var myModal = bootstrap.Modal.getInstance(document.getElementById('editAModal'));
                    myModal.hide(); // Hide the modal

                    if (data.success) {
                        document.querySelector('#successModal .modal-body').textContent = data.message;
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        successModal._element.addEventListener('hidden.bs.modal', function() {
                            window.location.reload();
                        });
                    } else {
                        document.querySelector('#errorModal .modal-body').textContent = data.message || "An error occurred.";
                        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        errorModal.show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    var myModal = bootstrap.Modal.getInstance(document.getElementById('editAModal'));
                    myModal.hide();

                    document.querySelector('#errorModal .modal-body').textContent = 'A network error occurred. Please try again.';
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                });
            };

            // Define openEditModal inside the DOMContentLoaded listener
            window.openEditModal = function(placementId, studentName, programMajor, schoolPlaced, currentAdviserId) {
                document.getElementById('modalStudentId').value = placementId;
                document.getElementById('modalStudentName').textContent = studentName;
                document.getElementById('modalProgramMajor').textContent = programMajor;
                document.getElementById('modalSchoolPlaced').textContent = schoolPlaced;

                // Set the selected adviser in the dropdown
                var adviserSelect = document.getElementById('aSelect');
                if (currentAdviserId) {
                    adviserSelect.value = currentAdviserId;
                } else {
                    adviserSelect.value = '';
                }

                var myModal = new bootstrap.Modal(document.getElementById('editAModal'));
                myModal.show();
            };
        });

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
            let filename = 'List of Pre-Service Teacher Placements';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Student Number", "Pre-Service Teacher Name", "Program-Major", "Placement School", "Adviser", "Cooperating Teacher"];
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

        function exportTableToExcel(filename) {
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

            const actionsColumnIndex = 6;

            // Remove the "Actions" column from the cloned table
            table.querySelectorAll('thead th')[actionsColumnIndex].remove();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.deleteCell(actionsColumnIndex);
            });

            const currentDate = new Date().toLocaleString();

            let win = window.open('', '_blank');
            if (!win) {
                alert("Pop-up blocked! Please allow pop-ups for this site.");
                return;
            }
            win.document.write('<html><head><title>Approved Placements with Adviser and Cooperating Teacher</title>');
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

            win.document.write('<div class="header-container"><p class="title-text">List of Approved Placements with Adviser and Cooperating Teacher</p></div>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Student Number</th><th>Pre-Service Teacher</th><th>Program-Major</th><th>Placement School</th><th>Adviser</th><th>Cooperating Teacher</th><tr></thead>');
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