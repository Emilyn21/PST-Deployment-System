<?php
include 'includes/auth.php';

$academicYearName = '';
$semesterType = '';

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

if (isset($school_id)) {
    // Check if an active semester exists
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

        // Query placement data
        $stmt = $conn->prepare("
            SELECT 
                pl.id AS placement_id, 
                pst.student_number, 
                CONCAT(u.last_name, ', ', u.first_name, 
                CASE 
                    WHEN u.middle_name IS NOT NULL AND u.middle_name != '' THEN CONCAT(' ', u.middle_name)
                    ELSE ''
                END) AS student_name,
                u.email,
                ay.academic_year_name,
                CONCAT(
                    COALESCE(p.program_abbreviation, 'N/A'),
                    CASE 
                        WHEN m.major_abbreviation IS NOT NULL THEN CONCAT('-', m.major_abbreviation)
                        ELSE ''
                    END
                ) AS program_major, 
                p.id AS program_id, 
                m.major_name AS major, 
                m.id AS major_id, 
                CASE 
                    WHEN COALESCE(u.street, u.barangay, u.city_municipality, u.province) IS NULL 
                    THEN ''
                    ELSE TRIM(
                        CONCAT(
                            COALESCE(u.street, ''), 
                            CASE WHEN u.street IS NOT NULL AND u.barangay IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(u.barangay, ''), 
                            CASE WHEN u.barangay IS NOT NULL AND u.city_municipality IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(u.city_municipality, ''), 
                            CASE WHEN u.city_municipality IS NOT NULL AND u.province IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(u.province, '')
                        )
                    )
                END AS address,
                pl.school_id, pl.status, pl.created_at, 
                cs.school_name, 
                cta.cooperating_teacher_id AS cooperating_teacher_id,
                ct.id,
                TRIM(
                    CONCAT(
                        advu.first_name, 
                        CASE 
                            WHEN advu.middle_name IS NOT NULL AND advu.middle_name != '' THEN CONCAT(' ', advu.middle_name) 
                            ELSE '' 
                        END, 
                        ' ', 
                        advu.last_name
                    )
                ) AS adviser_name,
                TRIM(
                    CONCAT(
                        ctu.first_name, 
                        CASE 
                            WHEN ctu.middle_name IS NOT NULL AND ctu.middle_name != '' THEN CONCAT(' ', ctu.middle_name) 
                            ELSE '' 
                        END, 
                        ' ', 
                        ctu.last_name
                    )
                ) AS ct_name,
                cta.date_assigned
            FROM 
                tbl_placement pl
            INNER JOIN 
                tbl_pre_service_teacher pst ON pst.id = pl.pre_service_teacher_id
            INNER JOIN 
                tbl_user u ON pst.user_id = u.id
            INNER JOIN
                tbl_semester s ON pst.semester_id = s.id
            INNER JOIN
                tbl_academic_year ay ON s.academic_year_id = ay.id
            INNER JOIN 
                tbl_program p ON pst.program_id = p.id
            LEFT JOIN 
                tbl_major m ON pst.major_id = m.id
            INNER JOIN 
                tbl_school cs ON pl.school_id = cs.id
            LEFT JOIN 
                tbl_cooperating_teacher_assignment cta ON cta.placement_id = pl.id
            LEFT JOIN 
                tbl_cooperating_teacher ct ON cta.cooperating_teacher_id = ct.id
            LEFT JOIN 
                tbl_user ctu ON ct.user_id = ctu.id
            LEFT JOIN 
                tbl_adviser_assignment aa ON aa.placement_id = pl.id
            LEFT JOIN 
                tbl_adviser adv ON aa.adviser_id = adv.id
            LEFT JOIN 
                tbl_user advu ON adv.user_id = advu.id
            WHERE 
                pl.school_id = ?
                AND u.isDeleted = 0
                AND pl.status = 'approved'
                AND pl.isDeleted = 0
                AND pst.semester_id = ?
            ORDER BY
                (CASE 
                    WHEN aa.adviser_id IS NULL AND ct.id IS NULL THEN 1
                    WHEN aa.adviser_id IS NULL AND ct.id IS NOT NULL THEN 2
                    WHEN aa.adviser_id IS NOT NULL AND ct.id IS NULL THEN 3
                    ELSE 4
                END),
                u.last_name ASC,
                pl.created_at DESC,
                pl.approved_by ASC");

        $stmt->bind_param('ii', $school_id, $activeSemesterId);
        $stmt->execute();
        $query = $stmt->get_result();
        $stmt->close();
    } else {
        echo "Error: No active semester found.";
    }
    $stmt2 = $conn->prepare("SELECT school_name FROM tbl_school WHERE id = ?");
    $stmt2->bind_param('i', $school_id);
    $stmt2->execute();
    $result2Result = $stmt2->get_result();
    if ($result2Row = $result2Result->fetch_assoc()) {
        $schoolname = $result2Row['school_name'];
    }
} else {
    echo "Error: No school assigned to this admin.";
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
    <title>Cooperating Teacher Assignment - School Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        @media (max-width: 576px) {
            .flex-wrap-nowrap {
                flex-wrap: wrap !important;
            }
        }

        .btn-action {
            margin-top: 0.2rem;
        }

        .form-group {
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
                    <h1 class="mt-5 h3" id="main-heading">Cooperating Teacher Assignment</h1>
                    <ol class="breadcrumb mb_4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cooperating Teacher Assignment</li>
                    </ol>
                    <!-- Dashboard Summary Cards -->

                    <?php
                    // Query to count total placements with both adviser and cooperating teacher assigned
                    $totalBothQuery = "
                        SELECT COUNT(*) AS total_placements 
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
                        JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                        JOIN tbl_adviser_assignment aa ON tpl.id = aa.placement_id
                        WHERE tpl.school_id = ? AND tpl.isDeleted = 0 AND tpl.status = 'approved' AND tpst.semester_id = ?";
                    $stmtBoth = $conn->prepare($totalBothQuery);
                    $stmtBoth->bind_param('ii', $school_id, $activeSemesterId);
                    $stmtBoth->execute();
                    $totalBothResult = $stmtBoth->get_result();
                    $totalBoth = $totalBothResult->fetch_assoc()['total_placements'];

                    // Query to count placements with only a cooperating teacher assigned
                    $totalCooperatingQuery = "
                        SELECT COUNT(*) AS total_placements
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
                        JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                        LEFT JOIN tbl_adviser_assignment aa ON tpl.id = aa.placement_id
                        WHERE aa.placement_id IS NULL AND tpl.school_id = ? AND tpl.isDeleted = 0 AND tpl.status = 'approved' AND tpst.semester_id = ?";
                    $stmtCooperating = $conn->prepare($totalCooperatingQuery);
                    $stmtCooperating->bind_param('ii', $school_id, $activeSemesterId);
                    $stmtCooperating->execute();
                    $totalCooperatingResult = $stmtCooperating->get_result();
                    $totalCooperating = $totalCooperatingResult->fetch_assoc()['total_placements'];

                    // Query to count placements with no cooperating teacher or adviser assigned
                    $totalNoneQuery = "
                        SELECT COUNT(*) AS total_placements
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
                        LEFT JOIN tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
                        LEFT JOIN tbl_adviser_assignment aa ON tpl.id = aa.placement_id
                        WHERE cta.placement_id IS NULL AND aa.placement_id IS NULL 
                        AND tpl.school_id = ? AND tpl.isDeleted = 0 AND tpl.status = 'approved' AND tpst.semester_id = ?";
                    $stmtNone = $conn->prepare($totalNoneQuery);
                    $stmtNone->bind_param('ii', $school_id, $activeSemesterId);
                    $stmtNone->execute();
                    $totalNoneResult = $stmtNone->get_result();
                    $totalNone = $totalNoneResult->fetch_assoc()['total_placements'];
                    ?>

                    <div class="row">
                        <!-- Placements with Both Adviser and Cooperating Teacher -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Both Adviser and Cooperating Teacher">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie me-2"></i>
                                        <span>Both Adviser and Cooperating Teacher</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalBoth ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Placements with Only Cooperating Teacher -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Only Cooperating Teacher">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>
                                        <span>Only Cooperating Teacher</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalCooperating ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Placements with Neither Adviser nor Cooperating Teacher -->
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

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                Manage cooperating teacher assignments for approved pre-service teacher placements.
                            </p>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>Approved Pre-Service Teacher Placements
                        </div>
                        <div class="card-body">
                            <div id="semesterInfo" style="display: none;"><?php echo htmlspecialchars($semesterType); ?></div>
                            <div id="academicYearInfo" style="display: none;"><?php echo htmlspecialchars($academicYearName); ?></div>
                            <div id="schoolNameInfo" style="display: none;"><?php echo htmlspecialchars($schoolname); ?></div>
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV()" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel()" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Approved Pre-Service Teacher Placements Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Pre-Service Teacher</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Adviser</th>
                                        <th scope="col" role="columnheader">Cooperating Teacher</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup"> 
                                    <?php while ($row = $query->fetch_assoc()): ?>
                                    <tr role="row">
                                        <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                                            <button type="button" class="btn btn-sm btn-warning btn-action" 
                                                onclick="openEditModal(
                                                    <?= htmlspecialchars($row['placement_id'], ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['student_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['program_major']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['school_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= isset($row['cooperating_teacher_id']) ? htmlspecialchars($row['cooperating_teacher_id'], ENT_QUOTES, 'UTF-8') : 'null'; ?>
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

    <!-- Edit Cooperating Teacher Modal -->
    <div class="modal fade" id="editCTModal" role="dialog" tabindex="-1" aria-labelledby="editCTModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editCTModalLabel"><i class="fas fa-edit"></i> Assign Cooperating Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST" action="functions/process-ct-assignment.php">
                    <div class="modal-body">
                        <input type="hidden" id="modalStudentId" name="placement_id" />
                        <p><strong>Student Name:</strong> <span id="modalStudentName"></span></p>
                        <p><strong>Program - Major:</strong> <span id="modalProgramMajor"></span></p>
                        <p><strong>School Placed:</strong> <span id="modalSchoolPlaced"></span></p>
                        <div class="mb-3">
                            <label for="ctSelect" class="form-label">Cooperating Teacher: </label>
                            <select  id="ctSelect" name="cooperating_teacher_id" required class="form-select">
                                <?php
                                // Get the assigned adviser for the current placement
                                $assigned_ct_id = null; // Default value for no adviser assigned
                                $placement_id = $row['placement_id']; // Assuming placement_id is available

                                $assigned_ct_query = "
                                    SELECT cooperating_teacher_id 
                                    FROM tbl_cooperating_teacher_assignment 
                                    WHERE placement_id = ? 
                                    LIMIT 1"; // Query to check for an assigned adviser

                                $stmt_assigned_ct = $conn->prepare($assigned_ct_query);
                                $stmt_assigned_ct->bind_param("i", $placement_id);
                                $stmt_assigned_ct->execute();
                                $result_assigned_ct = $stmt_assigned_ct->get_result();

                                if ($result_assigned_ct->num_rows > 0) {
                                    $assigned_ct_row = $result_assigned_ct->fetch_assoc();
                                    $assigned_ct_id = $assigned_ct_row['cooperating_teacher_id'];
                                }

                                // Create the "Select Adviser" option and disable it if an adviser is assigned
                                echo '<option value="" disabled ' . (is_null($assigned_ct_id) ? 'selected' : '') . '>Select Cooperating Teacher</option>';

                                // Fetch cooperating teachers directly
                                $teacher_query = "
                                    SELECT ct.id,
                                        TRIM(
                                            CONCAT(
                                                u.first_name, 
                                                CASE 
                                                    WHEN u.middle_name IS NOT NULL AND u.middle_name != '' THEN CONCAT(' ', u.middle_name) 
                                                    ELSE '' 
                                                END, 
                                                ' ', 
                                                u.last_name
                                            )
                                        ) AS teacher_name
                                    FROM tbl_cooperating_teacher ct
                                    INNER JOIN tbl_user u ON ct.user_id = u.id
                                    WHERE ct.school_id = ? AND u.isDeleted = 0 AND u.account_status = 'active'";

                                $stmt_teacher = $conn->prepare($teacher_query);
                                $stmt_teacher->bind_param('i', $school_id);
                                $stmt_teacher->execute();
                                $teacher_result = $stmt_teacher->get_result();

                                if ($teacher_result && $teacher_result->num_rows > 0) {
                                    while ($teacher_row = $teacher_result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($teacher_row['id']) . "'>" . htmlspecialchars($teacher_row['teacher_name']) . "</option>";
                                    }
                                } else {
                                    echo "<option value=''>No teachers available</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                    </div>
                </form>
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
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" role="dialog" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel"><i class="fas fa-times"></i> Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
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
                        noRows: "No active placements have been assigned to the school yet."
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

        function openEditModal(placementId, studentName, programMajor, schoolPlaced, currentCTId) {
            // Set the hidden placement ID field
            document.getElementById('modalStudentId').value = placementId;

            // Set the text for the modal fields
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalProgramMajor').textContent = programMajor;
            document.getElementById('modalSchoolPlaced').textContent = schoolPlaced;

            // Set the selected adviser in the dropdown
            var cooperatingTeacherSelect = document.getElementById('ctSelect');
            if (currentCTId) {
                cooperatingTeacherSelect.value = currentCTId;
            } else {
                cooperatingTeacherSelect.value = '';  // Default to no selection if no ct is assigned
            }

            // Show the modal
            var myModal = new bootstrap.Modal(document.getElementById('editCTModal'));
            myModal.show();
        }

        // Move the DOMContentLoaded event listener outside the function
        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.getElementById('editForm');

            // Set form submission handling for the modal
            editForm.onsubmit = function (e) {
                e.preventDefault(); // Prevent the default form submission

                const formData = new FormData(this);

                // Use fetch API to send the request
                fetch('functions/process-ct-assignment.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json' // Indicate that the response will be JSON
                    }
                })
                .then(response => response.json()) // Parse the JSON response
                .then(data => {
                    var myModal = bootstrap.Modal.getInstance(document.getElementById('editCTModal')); // Get the modal instance
                    myModal.hide(); // Hide the modal

                    if (data.success) {
                        // Show success modal with the message
                        document.querySelector('#successModal .modal-body').textContent = data.message;
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        successModal._element.addEventListener('hidden.bs.modal', function() {
                            window.location.reload(); // Optionally reload the page after the modal is closed
                        });
                    } else {
                        // Show error modal with the error message
                        document.querySelector('#errorModal .modal-body').textContent = data.message || "An error occurred.";
                        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        errorModal.show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    var myModal = bootstrap.Modal.getInstance(document.getElementById('editCTModal')); // Get the modal instance
                    myModal.hide(); // Hide the modal in case of error

                    // Show error modal in case of a network error
                    document.querySelector('#errorModal .modal-body').textContent = 'A network error occurred. Please try again.';
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                });
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
            let filename = 'List of Pre-Service Teacher Placement';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Pre-Service Teacher", "Program-Major", "Adviser", "Cooperating Teacher"];
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
            let filename = 'List of Pre-Service Teacher Placements';

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

            const actionsColumnIndex = 4;

            // Remove the "Actions" column from the cloned table
            if (table) {
                table.querySelectorAll('thead th')[actionsColumnIndex]?.remove();
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.deleteCell(actionsColumnIndex);
                });
            }

            const currentDate = new Date().toLocaleString();

            // Fetch semester and academic year from hidden elements
            let semester = document.getElementById('semesterInfo')?.textContent || '';
            const academicYear = document.getElementById('academicYearInfo')?.textContent || '';
            const schoolName = document.getElementById('schoolNameInfo')?.textContent || '';

            // Capitalize the first letter of the semester value if it's available
            if (semester) {
                semester = semester.charAt(0).toUpperCase() + semester.slice(1).toLowerCase();
            }

            let win = window.open('', '_blank');
            if (!win) {
                alert("Pop-up blocked! Please allow pop-ups for this site.");
                return;
            }
            win.document.write('<html><head><title>List of Approved Pre-Service Teacher Placements</title>');
            win.document.write(`
                <style>
                    @page {
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
                    .generated-text { font-size: 10pt; font-style: italic; margin-top: 15px; }
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

            // Modify the title to include semester and academic year, or just "List of Pre-Service Teachers" if not available
            let title = 'List of Approved Pre-Service Teacher Placements';
            if (semester && academicYear) {
                title += ` for ${semester} Semester A.Y. ${academicYear}`;
            } else if (semester) {
                title += ` for ${semester} Semester`;
            } else if (academicYear) {
                title += ` A.Y. ${academicYear}`;
            }

            win.document.write(`<div class="header-container"><p class="title-text">${title}</p></div>`);
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Pre-Service Teacher</th><th>Program - Major</th><th>Adviser</th><th>Cooperating Teacher</th><tr></thead>');
            win.document.write('<tbody>');

            table.querySelectorAll('tbody tr').forEach(row => {
                let cells = Array.from(row.cells);
                win.document.write('<tr>' + cells.map(cell => `<td>${cell.innerText}</td>`).join('') + '</tr>');
            });

            win.document.write('</tbody></table>');
            win.document.write(`
                <div class="generated-text">
                    Generated by <b>${schoolName}</b> in partnership with Cavite State University
                </div>
                <div class="generated-text">
                    Date and Time Generated: ${currentDate}
                </div>
            `);

            win.document.write(`
                <script>
                    window.onload = function() {
                        window.print();
                    };
                <\/script>
            `);

            win.document.write('</body></html>');
            win.document.close();
        }
    </script>
</body>

</html>