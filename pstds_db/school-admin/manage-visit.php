<?php
include 'includes/auth.php';

$loggedInUserId = $user_id;

$schoolAdminQuery = "SELECT school_id FROM tbl_school_admin WHERE user_id = ?";
$stmt = $conn->prepare($schoolAdminQuery);
$stmt->bind_param("i", $loggedInUserId);
$stmt->execute();
$schoolAdminResult = $stmt->get_result();

// Check if the user is a school admin and fetch their school_id
if ($schoolAdminResult && $schoolAdminResult->num_rows > 0) {
    $schoolAdminRow = $schoolAdminResult->fetch_assoc();
    $schoolId = $schoolAdminRow['school_id'];

    $visitsQuery = "
        SELECT v.id, v.title, v.description, v.status, v.visit_date, v.visit_time, v.confirmed_at, v.created_at, 
               at.type_name, s.school_name
        FROM tbl_visit AS v
        JOIN tbl_visit_types AS at ON v.visit_type_id = at.id
        JOIN tbl_school AS s ON v.school_id = s.id
        WHERE v.school_id = ? AND v.isDeleted = 0
        ORDER BY 
            FIELD(v.status, 'pending', 'confirmed', 'denied'),
            v.created_at DESC
    ";

    $stmtVisits = $conn->prepare($visitsQuery);
    $stmtVisits->bind_param("i", $schoolId);
    $stmtVisits->execute();
    $visitsResult = $stmtVisits->get_result();
} else {
    echo "No school ID found for the logged-in user.";
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
    <title>Manage Visits - School Admin</title>
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
        th:nth-child(4), td:nth-child(4) {
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
                    <h1 class="mt-5 h3" id="main-heading">Manage Visits</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Visits</li>
                    </ol>

                    <!-- Dashboard Summary Cards -->
                    <?php
                    // Assume the school admin's school_id has already been fetched
                    if (isset($schoolId)) {

                        // Query to fetch total visits for the specific school
                        $totalVisitsQuery = "
                            SELECT COUNT(*) as total 
                            FROM tbl_visit 
                            WHERE school_id = ? 
                            AND isDeleted = 0";
                        $stmtTotalVisits = $conn->prepare($totalVisitsQuery);
                        $stmtTotalVisits->bind_param("i", $schoolId);
                        $stmtTotalVisits->execute();
                        $totalVisitsResult = $stmtTotalVisits->get_result();
                        $totalVisits = mysqli_fetch_assoc($totalVisitsResult)['total'];

                        // Query to fetch confirmed visits for the specific school
                        $confirmedVisitsQuery = "
                            SELECT COUNT(*) as confirmed 
                            FROM tbl_visit 
                            WHERE school_id = ? 
                            AND status = 'confirmed' 
                            AND isDeleted = 0";
                        $stmtConfirmedVisits = $conn->prepare($confirmedVisitsQuery);
                        $stmtConfirmedVisits->bind_param("i", $schoolId);
                        $stmtConfirmedVisits->execute();
                        $confirmedVisitsResult = $stmtConfirmedVisits->get_result();
                        $confirmedVisits = mysqli_fetch_assoc($confirmedVisitsResult)['confirmed'];

                        // Query to fetch pending visits for the specific school
                        $pendingVisitsQuery = "
                            SELECT COUNT(*) as pending 
                            FROM tbl_visit 
                            WHERE school_id = ? 
                            AND status = 'pending'  
                            AND isDeleted = 0";
                        $stmtPendingVisits = $conn->prepare($pendingVisitsQuery);
                        $stmtPendingVisits->bind_param("i", $schoolId);
                        $stmtPendingVisits->execute();
                        $pendingVisitsResult = $stmtPendingVisits->get_result();
                        $pendingVisits = mysqli_fetch_assoc($pendingVisitsResult)['pending'];

                        // Query to fetch denied visits for the specific school
                        $deniedVisitsQuery = "
                            SELECT COUNT(*) as denied 
                            FROM tbl_visit 
                            WHERE school_id = ? 
                            AND status = 'denied'  
                            AND isDeleted = 0";
                        $stmtDeniedVisits = $conn->prepare($deniedVisitsQuery);
                        $stmtDeniedVisits->bind_param("i", $schoolId);
                        $stmtDeniedVisits->execute();
                        $deniedVisitsResult = $stmtDeniedVisits->get_result();
                        $deniedVisits = mysqli_fetch_assoc($deniedVisitsResult)['denied'];

                    } else {
                        echo "No school ID found for the logged-in user.";
                    }
                    ?>

                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Visits">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <span>Total Received Visits</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalVisits ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Confirmed Visits">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span>Confirmed Visits</span>
                                    </div>
                                    <h3 class="mb-0"><?= $confirmedVisits ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Pending Visits">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock me-2"></i>
                                        <span>Pending Visits</span>
                                    </div>
                                    <h3 class="mb-0"><?= $pendingVisits ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4" role="region" aria-label="Denied Visits">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <span>Denied Visits</span>
                                    </div>
                                    <h3 class="mb-0"><?= $deniedVisits ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">Manage and monitor all visits from Cavite State University College of Education Teacher Education Department. You can confirm or decline their status as needed.</p>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Visits
                        </div>

                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Visits Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Visit Type</th>
                                        <th scope="col" role="columnheader">Date and Time of Visit</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>

                                <tbody role="rowgroup">
                                    <?php while ($row = $visitsResult->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td role="cell"><?= $row['type_name']; ?></td>
                                            <td role="cell">
                                                <?php 
                                                // Format the visit date and time
                                                $formattedDate = date('M. j, Y', strtotime($row['visit_date']));
                                                $formattedTime = date('g:i A', strtotime($row['visit_time']));
                                                echo "$formattedDate, $formattedTime";
                                                ?>
                                            </td>

                                            <td role="cell">
                                                <?php if ($row['status'] == 'confirmed'): ?>
                                                    <span class="badge bg-success">Confirmed</span>
                                                <?php elseif ($row['status'] == 'denied'): ?>
                                                    <span class="badge bg-danger">Declined</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>

                                            <td role="cell">
                                                <?php
                                                    // Check if there is a related entry in tbl_visit_reschedule for this visit
                                                    $rescheduleCheckQuery = "SELECT 1 FROM tbl_visit_reschedule WHERE visit_id = ? AND isDeleted = 0 LIMIT 1";
                                                    $stmtRescheduleCheck = $conn->prepare($rescheduleCheckQuery);
                                                    $stmtRescheduleCheck->bind_param("i", $row['id']);
                                                    $stmtRescheduleCheck->execute();
                                                    $stmtRescheduleCheck->store_result();
                                                    $isRescheduled = $stmtRescheduleCheck->num_rows > 0; // True if there is a reschedule request
                                                ?>

                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <!-- Request Button -->
                                                    <button type="button" class="btn btn-sm btn-warning btn-request btn-action"
                                                        onclick="openRequestModal(<?= $row['id']; ?>,
                                                            '<?= htmlspecialchars($row['visit_date'], ENT_QUOTES, 'UTF-8'); ?>',
                                                            '<?= htmlspecialchars($row['visit_time'], ENT_QUOTES, 'UTF-8'); ?>')"
                                                        <?php if ($isRescheduled): ?> disabled <?php endif; ?>>
                                                        <i class="fas fa-calendar"></i> Reschedule
                                                    </button>
                                                <?php endif; ?>

                                                <!-- View Button -->
                                                <button class="btn btn-sm btn-primary btn-view btn-action" 
                                                    onclick="openViewModal(
                                                        <?= htmlspecialchars(json_encode($row['id']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['type_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['visit_date'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['visit_time'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['title'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['description'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['created_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['confirmed_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <i class="fa fa-eye"></i>
                                                </button>

                                                <!-- Confirm and Deny Buttons -->
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success btn-action" 
                                                            onclick="openConfirmModal(<?= $row['id']; ?>, 'confirm')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger btn-action" 
                                                            onclick="openConfirmModal(<?= $row['id']; ?>, 'deny')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
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

    <!-- Request Schedule Modal -->
    <div class="modal fade" id="requestScheduleModal" role="dialog" tabindex="-1" aria-labelledby="requestScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="requestScheduleModalLabel"><i class="fas fa-paper-plane"></i> Submit Reschedule Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note"><strong>Note:</strong> You can only submit a request for rescheduling once. Please make sure to double-check your details before submitting to avoid any further changes.<br>
                            <br>Confirming the visit after submission will cancel the request automatically, and the original visit schedule will be confirmed instead.</p>
                        </div>
                    </div>

                    <form id="requestScheduleForm">
                        <input type="hidden" name="visit_id" id="visitId">

                        <div class="mb-3">
                            <label for="visitDate" class="form-label">Visit Date</label>
                            <input type="date" class="form-control" name="visit_date" id="visitDate" required>
                        </div>

                        <div class="mb-3">
                            <label for="visitTime" class="form-label">Visit Time</label>
                            <input type="time" class="form-control" name="visit_time" id="visitTime" required>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="requestScheduleForm" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Modal -->
    <div class="modal fade" id="submitModal" role="dialog" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white" id="submitModalHeader">
                    <h5 class="modal-title" id="submitModalLabel"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    The reschedule request has been submitted successfully.
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewModalLabel">
                        <i class="fa fa-eye"></i> Visit Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-list"></i> Visit Type</th>
                                    <td id="viewType"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-calendar-alt"></i> Date of Visit</th>
                                    <td id="viewDate"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-clock"></i> Time of Visit</th>
                                    <td id="viewTime"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-heading"></i> Title</th>
                                    <td id="viewTitle"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-file-alt"></i> Content</th>
                                    <td id="viewDescription"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-info-circle"></i> Status</th>
                                    <td>
                                        <span id="viewStatus" class="badge"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-calendar"></i> Creation Date</th>
                                    <td id="viewCreatedAt"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Action Modal -->
    <div class="modal fade" id="confirmActionModal" role="dialog" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" id="confirmActionModalHeader">
                    <h5 class="modal-title" id="confirmActionModalLabel"><i class="fa fa-edit"></i> Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    Are you sure you want to <span id="actionType"></span> this visit?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmButton" class="btn btn-primary">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" role="dialog" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" id="successModalHeader">
                    <h5 class="modal-title" id="successModalLabel"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    The visit has been <span id="successMessage"></span>.
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

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                const dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No school visits have been scheduled yet."
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

            window.openConfirmModal = function (visitId, actionType) {
                // Action Type Display
                const actionText = actionType === 'confirm' ? 'confirm' : 'deny';
                document.getElementById('actionType').textContent = actionText;

                // Modify Modal Header Dynamically
                const modalHeader = document.querySelector('#confirmActionModal .modal-header');
                const headerClass = actionType === 'confirm' ? 'modal-header bg-success text-white' : 'modal-header bg-danger text-white';
                modalHeader.className = `modal-header ${headerClass}`;

                const confirmButton = document.getElementById('confirmButton');

                // Remove any previous handlers to avoid stacking
                confirmButton.removeEventListener('click', confirmButton.handler);

                confirmButton.handler = function () {
                    const data = { visit_id: visitId, action: actionType };

                    fetch('functions/update-visit.php', {
                        method: 'POST',
                        body: JSON.stringify(data),
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmActionModal'));

                        if (data.success) {
                            confirmModal.hide();

                            // Show Success Modal
                            const successHeader = document.getElementById('successModalHeader');
                            const successMessage = document.getElementById('successMessage');

                            // Update header and message for the success modal
                            successHeader.className = headerClass;
                            successMessage.textContent = actionType === 'confirm' ? 'confirmed' : 'denied';

                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();

                            // Reload page after closing success modal
                            successModal._element.addEventListener('hidden.bs.modal', () => {
                                window.location.reload();
                            });
                        } else {
                            // Handle error with Error Modal
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

                // Attach the new event listener
                confirmButton.addEventListener('click', confirmButton.handler);

                // Show confirmation modal
                const confirmModal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
                confirmModal.show();
            };

            // Open the request reschedule modal and populate fields
            window.openRequestModal = function (visitId, visitDate, visitTime) {
                // Set the visitId in the hidden input field
                document.getElementById('visitId').value = visitId;

                // Set the visit date and time in the input fields
                document.getElementById('visitDate').value = visitDate;
                document.getElementById('visitTime').value = visitTime;

                // Show the Request Reschedule modal
                const requestModal = new bootstrap.Modal(document.getElementById('requestScheduleModal'));
                requestModal.show();
            };

            // Submit reschedule request
            document.getElementById('requestScheduleForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const visitId = document.getElementById('visitId').value;
                const visitDate = document.getElementById('visitDate').value;
                const visitTime = document.getElementById('visitTime').value;

                // Prepare the data to be sent
                const formData = new FormData();
                formData.append('visit_id', visitId);
                formData.append('visit_date', visitDate);
                formData.append('visit_time', visitTime);

                // Submit the form via fetch
                fetch('functions/process-visit-reschedule.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    // If the request was successful, show the success modal
                    if (data.status === 'success') {
                        const submitModal = new bootstrap.Modal(document.getElementById('submitModal'));
                        submitModal.show();

                        // Close the reschedule modal
                        const requestModal = bootstrap.Modal.getInstance(document.getElementById('requestScheduleModal'));
                        requestModal.hide();

                        // Reload the page after the submit modal is closed
                        submitModal._element.addEventListener('hidden.bs.modal', function () {
                            location.reload();
                        });
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('There was an error processing the request. Please try again.');
                });
            });
        });

        function openViewModal(id, typeName, visitDate, visitTime, title, description, status, createdAt, confirmedAt) {
            document.getElementById('viewTitle').textContent = title;
            document.getElementById('viewType').textContent = typeName;
            document.getElementById('viewDate').textContent = new Date(visitDate).toLocaleDateString('en-US', {
                month: 'short', // Abbreviated month (e.g., "Nov.")
                day: 'numeric', // Numeric day (e.g., "10")
                year: 'numeric' // Full year (e.g., "2024")
            });
            document.getElementById('viewTime').textContent = new Date('1970-01-01T' + visitTime).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
            document.getElementById('viewDescription').textContent = description;
            document.getElementById('viewStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
            document.getElementById('viewCreatedAt').textContent = new Date(createdAt).toLocaleString('en-US', {
                month: 'short', // Abbreviated month (e.g., "Nov.")
                day: 'numeric', // Numeric day (e.g., "10")
                year: 'numeric' // Full year (e.g., "2024")
            });

            // Update status badge dynamically with date if confirmed/denied
            const statusElement = document.getElementById('viewStatus');
            statusElement.className = 'badge'; // Reset class
            let statusText = status.charAt(0).toUpperCase() + status.slice(1); // Capitalize status

            if (status === 'confirmed') {
                statusElement.classList.add('bg-success'); // Green badge
                statusText += ` - ${new Date(confirmedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            } else if (status === 'denied') {
                statusElement.classList.add('bg-danger'); // Red badge
                statusText += ` - ${new Date(confirmedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            } else {
                statusElement.classList.add('bg-warning'); // Yellow badge for pending
            }

            statusElement.textContent = statusText; // Update text inside badge

            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
        }
    </script>
</body>
</html>