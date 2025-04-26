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
                pl.school_id, pl.start_date, pl.end_date, pl.status, pl.created_at, 
                cs.school_name 
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
            WHERE 
                pl.school_id = ?
                AND u.isDeleted = 0
                AND pl.isDeleted = 0
                AND pst.semester_id = ?
            ORDER BY
                CASE pl.status 
                    WHEN 'pending' THEN 1
                    WHEN 'approved' THEN 2
                    ELSE 3
                END,
                pl.created_at DESC");

        $stmt->bind_param('ii', $school_id, $activeSemesterId);
        $stmt->execute();
        $result = $stmt->get_result();
        } else {
    }

    $stmt2 = $conn->prepare("SELECT school_name FROM tbl_school WHERE id = ?");
    $stmt2->bind_param('i', $school_id);
    $stmt2->execute();
    $result2Result = $stmt2->get_result();
    if ($result2Row = $result2Result->fetch_assoc()) {
        $schoolname = $result2Row['school_name'];
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

    <title>Manage Placement Requests - School Admin</title>

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
        th:nth-child(6), td:nth-child(6) {
            white-space: nowrap;
            width: auto !important;
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
                    <h1 class="mt-5 h3" id="main-heading">Manage Placement Requests</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Placement Requests</li>
                    </ol>

                    <!-- Dashboard Summary Cards -->
                    <?php
                    // Query to count total placements
                    $totalPlacementsQuery = "SELECT COUNT(*) AS total_placements 
                        FROM tbl_placement tpl 
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                        JOIN tbl_user tu ON tpst.user_id = tu.id 
                        WHERE tpl.school_id = ? AND tpl.isDeleted = 0 AND tu.isDeleted = 0 AND tpst.semester_id = ?";

                    $stmtTotalPlacements = $conn->prepare($totalPlacementsQuery);
                    $stmtTotalPlacements->bind_param("ii", $school_id, $activeSemesterId);
                    $stmtTotalPlacements->execute();
                    $totalPlacementsResult = $stmtTotalPlacements->get_result();
                    $totalPlacements = mysqli_fetch_assoc($totalPlacementsResult)['total_placements'];


                    // Query to count approved placements
                    $totalApprovedPlacementsQuery = "SELECT COUNT(*) AS approved_placements 
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                        JOIN tbl_user tu ON tpst.user_id = tu.id 
                        WHERE tpl.school_id = ? AND tpl.status = 'approved' AND tpl.isDeleted = 0 AND tpst.semester_id = ?";

                    $stmtTotalApprovedPlacements = $conn->prepare($totalApprovedPlacementsQuery);
                    $stmtTotalApprovedPlacements->bind_param("ii", $school_id, $activeSemesterId);
                    $stmtTotalApprovedPlacements->execute();
                    $totalApprovedPlacementsResult = $stmtTotalApprovedPlacements->get_result();
                    $totalApprovedPlacements = mysqli_fetch_assoc($totalApprovedPlacementsResult)['approved_placements'];

                    // Query to count pending placements
                    $totalPendingPlacementsQuery = "SELECT COUNT(*) AS pending_placements 
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                        JOIN tbl_user tu ON tpst.user_id = tu.id 
                        WHERE tpl.school_id = ? AND tpl.status = 'pending' AND tpl.isDeleted = 0 AND tpst.semester_id = ?";

                    $stmtTotalPendingPlacements = $conn->prepare($totalPendingPlacementsQuery);
                    $stmtTotalPendingPlacements->bind_param("ii", $school_id, $activeSemesterId);
                    $stmtTotalPendingPlacements->execute();
                    $totalPendingPlacementsResult = $stmtTotalPendingPlacements->get_result();
                    $totalPendingPlacements = mysqli_fetch_assoc($totalPendingPlacementsResult)['pending_placements'];

                    // Query to count declined placements
                    $totalDeclinedPlacementsQuery = "SELECT COUNT(*) AS declined_placements 
                        FROM tbl_placement tpl
                        JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id 
                        JOIN tbl_user tu ON tpst.user_id = tu.id 
                        WHERE tpl.school_id = ? AND tpl.status = 'rejected' AND tpl.isDeleted = 0 AND tpst.semester_id = ?";

                    $stmtTotalDeclinedPlacements = $conn->prepare($totalDeclinedPlacementsQuery);
                    $stmtTotalDeclinedPlacements->bind_param("ii", $school_id, $activeSemesterId);
                    $stmtTotalDeclinedPlacements->execute();
                    $totalDeclinedPlacementsResult = $stmtTotalDeclinedPlacements->get_result();
                    $totalDeclinedPlacements = mysqli_fetch_assoc($totalDeclinedPlacementsResult)['declined_placements'];
                    ?>
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clipboard-list me-2"></i>
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
                                    <h3 class="mb-0"><?= $totalApprovedPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Pending Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock me-2"></i>
                                        <span>Pending Placements</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalPendingPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4" role="region" aria-label="Declined Placements">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <span>Declined Placements</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalDeclinedPlacements ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                You can view the pending pre-service teacher placed to your school. You can approve or decline each placement to update their status.
                            </p>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Pre-Service Teacher Placement Requests
                        </div>

                        <div class="card-body">
                            <div id="semesterInfo" style="display: none;"><?php echo htmlspecialchars($semesterType); ?></div>
                            <div id="academicYearInfo" style="display: none;"><?php echo htmlspecialchars($academicYearName); ?></div>
                            <div id="schoolNameInfo" style="display: none;"><?php echo htmlspecialchars($schoolname); ?></div>
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary" role="button">Copy</button>
                                    <button onclick="exportTableToCSV()" class="btn btn-secondary" role="button">CSV</button>
                                    <button onclick="exportTableToExcel()" class="btn btn-secondary" role="button">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary" role="button">Print</button>
                                </div>
                            </div>

                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Pre-Service Teacher Placement Requests Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Pre-Service Teacher</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Start Date</th>
                                        <th scope="col" role="columnheader">End Date</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Action</th>
                                    </tr>
                                </thead>

                                <tbody role="rowgroup"> 
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?php $formattedDate = date('M j, Y', strtotime($row['start_date'])); echo "$formattedDate"; ?></td>
                                            <td role="cell"><?php $formattedDate = date('M j, Y', strtotime($row['end_date'])); echo "$formattedDate"; ?></td>
                                            <td role="cell">
                                                <?php if ($row['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($row['status'] == 'rejected'): ?>
                                                    <span class="badge bg-danger">Declined</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td role="cell">
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <button type="button" class="btn btn-success btn-action btn-sm" 
                                                            onclick="openConfirmModal(<?= htmlspecialchars(json_encode($row['placement_id']), ENT_QUOTES, 'UTF-8'); ?>, 'approve')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-action btn-sm" 
                                                            onclick="openConfirmModal(<?= htmlspecialchars(json_encode($row['placement_id']), ENT_QUOTES, 'UTF-8'); ?>, 'decline')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-warning btn-action" 
                                                        onclick="openEditPlacementModal('<?= htmlspecialchars($row['placement_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', 
                                                                                        '<?= htmlspecialchars($row['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', 
                                                                                        '<?= htmlspecialchars($row['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')"
                                                        <?php if ($row['status'] == 'rejected'): ?> disabled <?php endif; ?>>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($result->num_rows == 0): ?>
                                        <tr role="row">
                                            <td colspan="10" class="text-center" role="cell">No students found.</td>
                                        </tr>
                                    <?php endif; ?>
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
    <div class="modal fade" id="editPlacementModal" role="dialog" tabindex="-1" aria-labelledby="editPlacementModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editPlacementModalLabel"><i class="fas fa-edit"></i> Edit Placement Dates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPlacementForm">
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="startDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="endDate" required>
                        </div>
                        <input type="hidden" name="placement_id" id="placementId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editPlacementForm" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Action Modal -->
    <div class="modal fade" id="confirmActionModal" role="dialog" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="confirmActionModalLabel"><i class="fa fa-edit"></i> Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to <span id="actionType"></span> this placement?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmButton">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" role="dialog" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white" id="successModalHeader">
                    <h5 class="modal-title" id="successModalLabel"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The placement has been <span id="successMessage"></span>.
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
                    <h5 class="modal-title" id="errorModalLabel"><i class="fas fa-times"></i> Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    There was an error processing the request. Please try again.
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
                        noRows: "No placements have been added to the school yet."
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

            window.openEditPlacementModal = function (placementId, startDate, endDate) {
                // Populate modal fields with existing data
                document.getElementById('placementId').value = placementId;
                document.getElementById('startDate').value = startDate || '';
                document.getElementById('endDate').value = endDate || '';

                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editPlacementModal'));
                editModal.show();
            }

            // Submit form via AJAX
            document.getElementById('editPlacementForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('functions/update-placement-request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide the edit modal
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editPlacementModal'));
                    if (editModal) {
                        editModal.hide();
                    }

                    if (data.success) {
                        // Show success modal
                        document.getElementById('successMessage').textContent = 'updated successfully';
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();

                        // Reload page when success modal is closed
                        document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
                            location.reload();
                        });

                    } else {
                        // Show error modal
                        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        errorModal.show();

                        // Reload page when error modal is closed
                        document.getElementById('errorModal').addEventListener('hidden.bs.modal', function () {
                            location.reload();
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    // Hide the edit modal
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editPlacementModal'));
                    if (editModal) {
                        editModal.hide();
                    }

                    // Show error modal
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();

                    // Reload page when error modal is closed
                    document.getElementById('errorModal').addEventListener('hidden.bs.modal', function () {
                        location.reload();
                    });
                });
            });

            window.openConfirmModal = function (placementId, actionType) {
                const actionText = actionType === 'approve' ? 'approve' : 'decline';
                const headerClass = actionType === 'approve' ? 'bg-success text-white' : 'bg-danger text-white';
                
                document.getElementById('actionType').textContent = actionText;

                // Update modal header color
                const modalHeader = document.querySelector('#confirmActionModal .modal-header');
                modalHeader.className = `modal-header ${headerClass}`;

                const confirmButton = document.getElementById('confirmButton');

                // Remove previous event listener to avoid stacking handlers
                confirmButton.removeEventListener('click', confirmButton.handler);

                confirmButton.handler = function () { // Store a new handler
                    const data = { placement_id: placementId, action: actionType };

                    fetch('functions/update-placement-status.php', {
                        method: 'POST',
                        body: JSON.stringify(data),
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'  // Ensure response is JSON
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmActionModal'));
                        if (data.success) {
                            confirmModal.hide(); // Hide confirm modal

                            // Show success modal with dynamic header
                            const successHeader = document.getElementById('successModalHeader');
                            const successMessage = document.getElementById('successMessage');

                            if (actionType === 'approve') {
                                successHeader.className = 'modal-header bg-success text-white'; // Green for approval
                                successMessage.textContent = 'approved';
                            } else {
                                successHeader.className = 'modal-header bg-danger text-white'; // Red for declinaion
                                successMessage.textContent = 'declined';
                            }

                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();

                            // Reload page on success modal close
                            successModal._element.addEventListener('hidden.bs.modal', () => {
                                window.location.reload();
                            });
                        } else {
                            // Show error modal
                            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                            errorModal.show();
                            console.error('Error:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('There was an error processing the request. Please try again.');
                    });
                };

                confirmButton.addEventListener('click', confirmButton.handler); // Add the new handler

                // Show the confirmation modal
                const confirmModal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
                confirmModal.show();
            };
        });

        function showSuccessModal(actionType) {
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            document.getElementById('successMessage').textContent = actionType === 'approve' ? 'approved' : 'rejected';
            successModal.show();
        }

        function showErrorModal() {
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
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
            let filename = 'List of Pre-Service Teacher Placement Requests';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Pre-Service Teacher Name", "Program-Major", "Start Date", "End Date", "Status"];
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
            let filename = 'List of Pre-Service Teacher Placement Requests';

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
            const actionsColumnIndex = 5;

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
            win.document.write('<html><head><title>List of Pre-Service Teacher Placements</title>');
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
            win.document.write('<thead><tr><th>Pre-Service Teacher</th><th>Program - Major</th><th>Start Date</th><th>End Date</th><th>Status</th></tr></thead>');
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