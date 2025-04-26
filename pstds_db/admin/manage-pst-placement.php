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
                tpst.id AS pst_id,
                tpst.student_number,
                CONCAT(tu.last_name, ', ', tu.first_name, 
                CASE 
                    WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
                    ELSE ''
                END) AS student_name,
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
                ) AS fml_name,
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
                CASE 
                    WHEN COALESCE(tu.street, tu.barangay, tu.city_municipality, tu.province) IS NULL 
                    THEN ''
                    ELSE TRIM(
                        CONCAT(
                            COALESCE(tu.street, ''),
                            CASE WHEN tu.street IS NOT NULL AND tu.barangay IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(tu.barangay, ''),
                            CASE WHEN tu.barangay IS NOT NULL AND tu.city_municipality IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(tu.city_municipality, ''),
                            CASE WHEN tu.city_municipality IS NOT NULL AND tu.province IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(tu.province, '')
                        )
                    )
                END AS address,
                ts.id AS school_id,
                ts.school_name,
                tpl.start_date,
                tpl.end_date,
                tpl.status,
                tpl.created_at,
                tpl.date_approved,
                tpl.created_by,
                tpl.approved_by
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
            JOIN 
                tbl_school ts ON tpl.school_id = ts.id
            WHERE 
                tpl.isDeleted = 0
                AND tu.account_status = 'active'
                AND tu.isDeleted = 0
                AND tpst.semester_id = ?
            ORDER BY
                CASE tpl.status 
                    WHEN 'pending' THEN 1
                    WHEN 'approved' THEN 2
                    ELSE 3
                END,
                tpl.created_at DESC");

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
    <title>Manage Pre-Service Teacher Placements - Admin</title>
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
                    <h1 class="mt-5 h3" id="main-heading">Manage Pre-Service Teacher Placements</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Pre-Service Teacher Placements</li>
                    </ol>

                    <?php
                    $totalQuery = "SELECT COUNT(*) AS total_placements 
                                   FROM tbl_placement tpl 
                                   JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                   JOIN tbl_user tu ON tpst.user_id = tu.id 
                                   WHERE tpl.isDeleted = 0 AND tu.isDeleted = 0 AND tu.account_status = 'active'";
                    $stmt = $conn->prepare($totalQuery);
                    $stmt->execute();
                    $totalResult = $stmt->get_result();
                    $totalPlacements = $totalResult->fetch_assoc()['total_placements'];
                    $stmt->close();

                    $approvedQuery = "SELECT COUNT(*) AS approved_placements 
                                      FROM tbl_placement tpl
                                      JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                      JOIN tbl_user tu ON tpst.user_id = tu.id 
                                      WHERE tpl.status = 'approved' AND tpl.isDeleted = 0 AND tu.account_status = 'active'";
                    $stmt = $conn->prepare($approvedQuery);
                    $stmt->execute();
                    $approvedResult = $stmt->get_result();
                    $approvedPlacements = $approvedResult->fetch_assoc()['approved_placements'];
                    $stmt->close();

                    $pendingQuery = "SELECT COUNT(*) AS pending_placements
                                     FROM tbl_placement tpl
                                     JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                     JOIN tbl_user tu ON tpst.user_id = tu.id 
                                     WHERE tpl.status = 'pending' AND tpl.isDeleted = 0 AND tu.account_status = 'active'";
                    $stmt = $conn->prepare($pendingQuery);
                    $stmt->execute();
                    $pendingResult = $stmt->get_result();
                    $pendingPlacements = $pendingResult->fetch_assoc()['pending_placements'];
                    $stmt->close();

                    $deniedQuery = "SELECT COUNT(*) AS denied_placements
                                    FROM tbl_placement tpl
                                    JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                                    JOIN tbl_user tu ON tpst.user_id = tu.id 
                                    WHERE tpl.status = 'rejected' AND tpl.isDeleted = 0 AND tu.account_status = 'active'";
                    $stmt = $conn->prepare($deniedQuery);
                    $stmt->execute();
                    $deniedResult = $stmt->get_result();
                    $deniedPlacements = $deniedResult->fetch_assoc()['denied_placements'];
                    $stmt->close();
                    ?>

                    <!-- Dashboard Summary Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Placements (2023-2024)">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-briefcase me-2"></i>
                                        <span>Total Placements</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Approved Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span>Approved Placements</span>
                                    </div>
                                    <h3 class="mb-0"><?= $approvedPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Pending Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-hourglass-half me-2"></i>
                                        <span>Pending Placements</span>
                                    </div>
                                    <h3 class="mb-0"><?= $pendingPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4" role="region" aria-label="Denied Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <span>Denied Placements</span>
                                    </div>
                                    <h3 class="mb-0"><?= $deniedPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                You can view and manage all pre-service teacher placements here. <strong>Please note:</strong> School placements are <em>final</em> and cannot be edited directly. 
                                If you need to change the school placement, you will need to <strong>delete the current placement</strong> and assign the pre-service teacher to a new school through the <a href="add-pst-placement.php">"Place Pre-Service Teachers"</a> page.
                            </p>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Pre-Service Teacher Placements
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
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Student Placement Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Student Number</th>
                                        <th scope="col" role="columnheader">Pre-Service Teacher</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Placement School</th>
                                        <th scope="col" role="columnheader">Start Date</th>
                                        <th scope="col" role="columnheader">End Date</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td class="text-center" role="cell"><?= htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?php $formattedDate = date('M j, Y', strtotime($row['start_date'])); echo "$formattedDate"; ?></td>
                                            <td role="cell"><?php $formattedDate = date('M j, Y', strtotime($row['end_date'])); echo "$formattedDate"; ?></td>
                                            <td role="cell">
                                                <?php if ($row['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($row['status'] == 'rejected'): ?>
                                                    <span class="badge bg-danger">Denied</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td role="cell">
                                                <button class="btn btn-sm btn-warning btn-action" 
                                                    onclick="openEditModal(
                                                        <?= $row['placement_id']; ?>,
                                                        <?= $row['student_number']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['student_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['program_major']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['address']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= $row['school_id']; ?>, 
                                                        <?= htmlspecialchars(json_encode($row['school_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['start_date']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['end_date']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?php 
                                                        // Format created_at
                                                        if (!empty($row['created_at'])) {
                                                            $formattedCreatedAt = date('M j, Y, g:i A', strtotime($row['created_at']));
                                                            echo htmlspecialchars(json_encode($formattedCreatedAt), ENT_QUOTES, 'UTF-8'); // Formatted created_at
                                                        } else {
                                                            echo htmlspecialchars(json_encode('Not available'), ENT_QUOTES, 'UTF-8'); // Default for empty created_at
                                                        }
                                                        ?>)"
                                                    <?php if ($row['status'] === 'rejected') echo 'disabled'; ?>>
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action" 
                                                    onclick="openDeleteModal(
                                                        <?= $row['placement_id']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['fml_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['school_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['status']), ENT_QUOTES, 'UTF-8'); ?>)"
                                                        <?php if ($row['status'] === 'approved') echo 'disabled'; ?>>
                                                    <i class="fa fa-trash"></i>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" role="dialog" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel"><i class="fa fa-edit"></i> Edit Placement of Pre-Service Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Student Number: </strong><span id="editStudentNumberDisplay"></span></p>
                    <p><strong>Name: </strong><span id="editNameDisplay"></span></p>
                    <p><strong>Program - Major: </strong><span id="editProgramMajorDisplay"></span></p>
                    <p><strong>Address: </strong><span id="editAddressDisplay"></span></p>
                    <p><strong>Date Created: </strong><span id="editDateCreatedDisplay"></span></p>
                    <form id="editForm" action="functions/update-pst-placement.php" method="POST">
                        <input type="hidden" id="editID" name="id" required>
                        <input type="hidden" id="editStudentNumber" name="studentNumber" required>
                        <input type="hidden" id="editName" name="name" required>
                        <input type="hidden" id="editProgramMajor" name="programMajor" required>
                        <input type="hidden" id="editAddress" name="address" required>
                        <input type="hidden" id="editDateCreated" name="dateCreated" readonly>
                        <div class="mb-3">
                            <label for="editSchool" class="form-label">School</label>
                            <input type="text" class="form-control" id="editSchoolDisplay" readonly disabled>
                            <input type="hidden" id="editSchool" name="school">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="editStartDate" name="startDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="editEndDate" name="endDate" required>
                                <div class="invalid-feedback">
                                    Please select a valid end date for the start date.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editForm" class="btn btn-primary">Save</button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-trash"></i> Delete Pre-Service Teacher Placement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    Are you sure you want to delete the <strong id="deleteStatus"></strong> placement of <strong id="deleteName"></strong> at <strong id="deleteSchool"></strong>?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
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
                    <p id="errorMessage">There's an error.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Success Modal -->
    <div class="modal fade" id="editSuccessModal" tabindex="-1" role="dialog" aria-labelledby="editSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editSuccessModalLabel"><i class="fas fa-check-circle"></i> Edit Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The placement details have been successfully updated.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Success Modal -->
    <div class="modal fade" id="deleteSuccessModal" tabindex="-1" role="dialog" aria-labelledby="deleteSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSuccessModalLabel"><i class="fa fa-trash"></i> Delete Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The placement has been successfully deleted.
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

    <script src="../js/topnav.js"></script>
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

            // Function to open the edit modal
            window.openEditModal = function(id, studentNumber, name, programMajor, address, school, schoolName, startDate, endDate, dateCreated) {
                document.getElementById('editID').value = id;
                document.getElementById('editStudentNumber').value = studentNumber;
                document.getElementById('editStudentNumberDisplay').textContent = studentNumber;
                document.getElementById('editName').value = name;
                document.getElementById('editNameDisplay').textContent = name;
                document.getElementById('editProgramMajor').value = programMajor;
                document.getElementById('editProgramMajorDisplay').textContent = programMajor;
                document.getElementById('editAddress').value = address;
                document.getElementById('editAddressDisplay').textContent = address;
                document.getElementById('editSchool').value = school; 
                document.getElementById('editSchoolDisplay').value = schoolName; 
                document.getElementById('editStartDate').value = startDate;
                document.getElementById('editEndDate').value = endDate;
                document.getElementById('editDateCreated').value = dateCreated;
                document.getElementById('editDateCreatedDisplay').textContent = dateCreated;

                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            };

            // Function to open the delete modal
            window.openDeleteModal = function(id, name, school, status) {
                document.getElementById('deleteName').textContent = name;
                document.getElementById('deleteSchool').textContent = school;
                document.getElementById('deleteStatus').textContent = status;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();

                const confirmDeleteButton = document.getElementById('confirmDeleteButton');
                confirmDeleteButton.onclick = function() {
                    fetch('functions/delete-pst-placement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            deleteModal.hide();  
                            const deleteSuccessModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'));
                            deleteSuccessModal.show();
                            deleteSuccessModal._element.addEventListener('hidden.bs.modal', function() {
                                window.location.reload();  
                            });
                        } else {
                            console.error('Error:', data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                };
            };

            const startDateField = document.getElementById('editStartDate');
            const endDateField = document.getElementById('editEndDate');
            const form = document.getElementById('editForm');

            startDateField.addEventListener('input', function () {
                validateDateRange(startDateField, endDateField);
            });

            endDateField.addEventListener('input', function () {
                validateDateRange(startDateField, endDateField);
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!validateDateRange(startDateField, endDateField)) return;

                const formData = new FormData(form);
                fetch('functions/update-pst-placement.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                        editModal.hide(); 

                        showModal('editSuccessModal'); 
                    } else {
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                        editModal.hide(); 
                        showErrorModal(data.message || "An error occurred.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal("A network error occurred. Please try again.");
                });
            });


            function validateDateRange(startInput, endInput) {
                const startDate = new Date(startInput.value);
                const endDate = new Date(endInput.value);

                resetValidation(startInput);
                resetValidation(endInput);

                if (!startInput.value || !endInput.value || startDate > endDate) {
                    if (!startInput.value) startInput.classList.add('is-invalid');
                    if (!endInput.value || startDate > endDate) endInput.classList.add('is-invalid');
                    return false;
                }
                return true;
            }

            function resetValidation(input) {
                input.classList.remove('is-invalid');
            }

            function showModal(modalId) {
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();
                modal._element.addEventListener('hidden.bs.modal', function() {
                    window.location.reload();
                });
            }

            function showErrorModal(message) {
                document.getElementById('errorMessage').textContent = message;
                new bootstrap.Modal(document.getElementById('errorModal')).show();
            }
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
            const headers = ["Student Number", "Pre-Service Teacher Name", "Program-Major", "Placement School", "Start Date", "End Date", "Status"];
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

            const actionsColumnIndex = 7;

            // Remove the "Actions" column from the cloned table
            table.querySelectorAll('thead th')[actionsColumnIndex].remove();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.deleteCell(actionsColumnIndex);
            });

            const currentDate = new Date().toLocaleString();

            // Fetch semester and academic year from hidden elements
            let semester = document.getElementById('semesterInfo')?.textContent || '';
            const academicYear = document.getElementById('academicYearInfo')?.textContent || '';

            // Capitalize the first letter of the semester value if it's available
            if (semester) {
                semester = semester.charAt(0).toUpperCase() + semester.slice(1).toLowerCase();
            }

            let win = window.open('', '_blank');
            if (!win) {
                alert("Pop-up blocked! Please allow pop-ups for this site.");
                return;
            }
            win.document.write('<html><head><title>List of Pre-Service Teacher Placements</title>');
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

            // Modify the title to include semester and academic year, or just "List of Pre-Service Teachers" if not available
            let title = 'List of Pre-Service Teacher Placements';
            if (semester && academicYear) {
                title += ` for ${semester} Semester A.Y. ${academicYear}`;
            } else if (semester) {
                title += ` for ${semester} Semester`;
            } else if (academicYear) {
                title += ` A.Y. ${academicYear}`;
            }

            win.document.write(`<div class="header-container"><p class="title-text">${title}</p></div>`);
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Student Number</th><th>Pre-Service Teacher</th><th>Program-Major</th><th>Placement School</th><th>Start Date</th><th>End Date</th><th>Status</th></tr></thead>');
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
