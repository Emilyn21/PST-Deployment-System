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

    $appointmentsQuery = "
        SELECT a.id, a.title, a.description, a.status, a.appointment_date, a.appointment_time, a.confirmed_at, a.created_at, 
               at.type_name, s.school_name 
        FROM tbl_appointment AS a
        JOIN tbl_appointment_types AS at ON a.appointment_type_id = at.id
        JOIN tbl_school AS s ON a.school_id = s.id
        WHERE a.school_id = ? AND a.isDeleted = 0
        ORDER BY 
            FIELD(a.status, 'pending', 'confirmed', 'denied'),
            a.created_at DESC
    ";

    $stmtAppointments = $conn->prepare($appointmentsQuery);
    $stmtAppointments->bind_param("i", $schoolId);
    $stmtAppointments->execute();
    $appointmentsResult = $stmtAppointments->get_result();
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
    <title>Manage Appointments - School Admin</title>
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
                    <h1 class="mt-4 h3" id="main-heading">Manage Appointments</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Appointments</li>
                    </ol>

                    <!-- Dashboard Summary Cards -->
                    <?php
                    // Assume the school admin's school_id has already been fetched
                    if (isset($schoolId)) {

                        // Query to fetch total appointments for the specific school
                        $totalAppointmentsQuery = "
                            SELECT COUNT(*) as total 
                            FROM tbl_appointment 
                            WHERE school_id = ? 
                            AND isDeleted = 0";
                        $stmtTotalAppointments = $conn->prepare($totalAppointmentsQuery);
                        $stmtTotalAppointments->bind_param("i", $schoolId);
                        $stmtTotalAppointments->execute();
                        $totalAppointmentsResult = $stmtTotalAppointments->get_result();
                        $totalAppointments = mysqli_fetch_assoc($totalAppointmentsResult)['total'];

                        // Query to fetch confirmed appointments for the specific school
                        $confirmedAppointmentsQuery = "
                            SELECT COUNT(*) as confirmed 
                            FROM tbl_appointment 
                            WHERE school_id = ? 
                            AND status = 'confirmed' 
                            AND isDeleted = 0";
                        $stmtConfirmedAppointments = $conn->prepare($confirmedAppointmentsQuery);
                        $stmtConfirmedAppointments->bind_param("i", $schoolId);
                        $stmtConfirmedAppointments->execute();
                        $confirmedAppointmentsResult = $stmtConfirmedAppointments->get_result();
                        $confirmedAppointments = mysqli_fetch_assoc($confirmedAppointmentsResult)['confirmed'];

                        // Query to fetch pending appointments for the specific school
                        $pendingAppointmentsQuery = "
                            SELECT COUNT(*) as pending 
                            FROM tbl_appointment 
                            WHERE school_id = ? 
                            AND status = 'pending'  
                            AND isDeleted = 0";
                        $stmtPendingAppointments = $conn->prepare($pendingAppointmentsQuery);
                        $stmtPendingAppointments->bind_param("i", $schoolId);
                        $stmtPendingAppointments->execute();
                        $pendingAppointmentsResult = $stmtPendingAppointments->get_result();
                        $pendingAppointments = mysqli_fetch_assoc($pendingAppointmentsResult)['pending'];

                        // Query to fetch denied appointments for the specific school
                        $deniedAppointmentsQuery = "
                            SELECT COUNT(*) as denied 
                            FROM tbl_appointment 
                            WHERE school_id = ? 
                            AND status = 'denied'  
                            AND isDeleted = 0";
                        $stmtDeniedAppointments = $conn->prepare($deniedAppointmentsQuery);
                        $stmtDeniedAppointments->bind_param("i", $schoolId);
                        $stmtDeniedAppointments->execute();
                        $deniedAppointmentsResult = $stmtDeniedAppointments->get_result();
                        $deniedAppointments = mysqli_fetch_assoc($deniedAppointmentsResult)['denied'];

                    } else {
                        echo "No school ID found for the logged-in user.";
                    }
                    ?>

                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Appointments">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <span>Total Received Appointments</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalAppointments ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Confirmed Appointments">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span>Confirmed Appointments</span>
                                    </div>
                                    <h3 class="mb-0"><?= $confirmedAppointments ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Pending Appointments">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock me-2"></i>
                                        <span>Pending Appointments</span>
                                    </div>
                                    <h3 class="mb-0"><?= $pendingAppointments ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-4" role="region" aria-label="Denied Appointments">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <span>Denied Appointments</span>
                                    </div>
                                    <h3 class="mb-0"><?= $deniedAppointments ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">Manage and monitor all appointments from Cavite State University College of Education Teacher Education Department. You can confirm or deny their status as needed.</p>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Appointments
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Appointments Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">ID</th>
                                        <th scope="col" role="columnheader">Appointment Type</th>
                                        <th scope="col" role="columnheader">Title</th>
                                        <th scope="col" role="columnheader">Date of Appointment</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $appointmentsResult->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td role="cell"><?= $row['id']; ?></td>
                                            <td role="cell"><?= $row['type_name']; ?></td>
                                            <td role="cell"><?= $row['title']; ?></td>
                                            <td role="cell">
                                                <?php 
                                                // Format the appointment date and time
                                                $formattedDate = date('M. j, Y', strtotime($row['appointment_date']));
                                                $formattedTime = date('g:i A', strtotime($row['appointment_time']));
                                                echo "$formattedDate, $formattedTime";
                                                ?>
                                            </td>
                                            <td role="cell">
                                                <?php if ($row['status'] == 'confirmed'): ?>
                                                    <span class="badge bg-success">Confirmed</span> <!-- Green badge for confirmed -->
                                                <?php elseif ($row['status'] == 'denied'): ?>
                                                    <span class="badge bg-danger">Denied</span> <!-- Red badge for denied -->
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span> <!-- Yellow badge for pending -->
                                                <?php endif; ?>
                                            </td>
                                            <td role="cell">
                                                <button class="btn btn-sm btn-primary btn-action" 
                                                    onclick="openViewModal(
                                                        <?= htmlspecialchars(json_encode($row['id']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['school_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['type_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['appointment_date'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['appointment_time'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['title'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['description'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['status'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['created_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['confirmed_at'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                                
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <button type="button" class="btn btn-action btn-success btn-sm" onclick="openConfirmModal(<?= $row['id']; ?>, 'confirm')"><i class="fas fa-check"></i> Confirm</button>
                                                    <button type="button" class="btn btn-action btn-danger btn-sm" onclick="openConfirmModal(<?= $row['id']; ?>, 'deny')"><i class="fas fa-times"></i> Deny</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($appointmentsResult->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center" role="cell">No appointments have been received yet.</td>
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

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" role="dialog" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewModalLabel"><i class="fas fa-eye"></i> Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Receiving School:</strong> <span id="viewSchool"></span></p>
                    <p><strong>Appointment Type:</strong> <span id="viewType"></span></p>
                    <p><strong>Date of Appointment:</strong> <span id="viewDate"></span></p>
                    <p><strong>Time of Appointment:</strong> <span id="viewTime"></span></p>
                    <p><strong>Title:</strong> <span id="viewTitle"></span></p>
                    <p><strong>Content:</strong> <span id="viewDescription"></span></p>
                    <p><strong>Status:</strong> <span id="viewStatus"></span></p>
                    <p><strong>Creation Date:</strong> <span id="viewCreatedAt"></span></p>
                    <p><strong>Date Confirmed:</strong> <span id="viewConfirmedAt"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                    Are you sure you want to <span id="actionType"></span> this appointment?
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
                    The appointment has been <span id="successMessage"></span>.
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
                new simpleDatatables.DataTable(datatablesSimple);
            }

            window.openConfirmModal = function (appointmentId, actionType) {
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

                confirmButton.handler = function () { // Define the new handler
                    const data = { appointment_id: appointmentId, action: actionType };

                    fetch('functions/update-appointment.php', {
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
                            confirmModal.hide(); // Hide confirmation modal

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
        });

        function openViewModal(id, schoolName, typeName, appointmentDate, appointmentTime, title, description, status, createdAt, confirmedAt) {
            document.getElementById('viewTitle').textContent = title;
            document.getElementById('viewSchool').textContent = schoolName;
            document.getElementById('viewType').textContent = typeName;
            document.getElementById('viewDate').textContent = new Date(appointmentDate).toLocaleDateString();
            document.getElementById('viewTime').textContent = new Date('1970-01-01T' + appointmentTime).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
            document.getElementById('viewDescription').textContent = description;
            document.getElementById('viewStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
            document.getElementById('viewCreatedAt').textContent = new Date(createdAt).toLocaleString();
            document.getElementById('viewConfirmedAt').textContent = confirmedAt ? new Date(confirmedAt).toLocaleString() : 'Pending';

            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
        }
        
    </script>
</body>

</html>
