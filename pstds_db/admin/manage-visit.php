<?php
include 'includes/auth.php';

// Modify the query to include the request details
$stmt = $conn->prepare("SELECT 
    v.id, v.title, v.description, v.status, v.visit_date, v.visit_time, v.confirmed_at, v.created_at, v.updated_at, 
    t.id AS visit_type_id, t.type_name, 
    s.id AS school_id, s.school_name, 
    res.visit_date AS requested_visit_date, res.visit_time AS requested_visit_time, res.status AS reschedule_status
FROM tbl_visit v
JOIN tbl_visit_types t ON v.visit_type_id = t.id
JOIN tbl_school s ON v.school_id = s.id
LEFT JOIN tbl_visit_reschedule res ON v.id = res.visit_id
WHERE v.isDeleted = 0
ORDER BY FIELD(v.status, 'pending', 'confirmed', 'denied'), v.created_at DESC");

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Manage Visits - Admin</title>
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
                    <h1 class="mt-5 h3" id="main-heading">Manage Visits</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Visits</li>
                    </ol>

                    <!-- Dashboard Summary Cards -->
                    <?php
                    // Queries to fetch the data
                    $totalVisitsQuery = "SELECT COUNT(*) as total FROM tbl_visit WHERE isDeleted = 0";
                    $totalVisitsResult = mysqli_query($conn, $totalVisitsQuery);
                    $totalVisits = mysqli_fetch_assoc($totalVisitsResult)['total'];

                    $confirmedVisitsQuery = "SELECT COUNT(*) as confirmed FROM tbl_visit WHERE status = 'confirmed' AND isDeleted = 0";
                    $confirmedVisitsResult = mysqli_query($conn, $confirmedVisitsQuery);
                    $confirmedVisits = mysqli_fetch_assoc($confirmedVisitsResult)['confirmed'];

                    $pendingVisitsQuery = "SELECT COUNT(*) as pending FROM tbl_visit WHERE status = 'pending'  AND isDeleted = 0";
                    $pendingVisitsResult = mysqli_query($conn, $pendingVisitsQuery);
                    $pendingVisits = mysqli_fetch_assoc($pendingVisitsResult)['pending'];

                    $deniedVisitsQuery = "SELECT COUNT(*) as denied FROM tbl_visit WHERE status = 'denied'  AND isDeleted = 0";
                    $deniedVisitsResult = mysqli_query($conn, $deniedVisitsQuery);
                    $deniedVisits = mysqli_fetch_assoc($deniedVisitsResult)['denied'];
                    ?>

                    <div class="row">
                        <!-- Total Visits -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Visits">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <span>Total Visits</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalVisits ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmed Visits -->
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

                        <!-- Pending Visits -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Pending Visits">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-hourglass-half me-2"></i>
                                        <span>Pending Visits</span>
                                    </div>
                                    <h3 class="mb-0"><?= $pendingVisits ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Denied Visits -->
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
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">Manage and monitor all Visits. Confirmed Visits cannot be edited.</p>
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
                                        <th scope="col" role="columnheader">Receiving School</th>
                                        <th scope="col" role="columnheader">Visit Type</th>
                                        <th scope="col" role="columnheader">Date of Visit</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>

                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()) : ?>
                                        <tr role="row">
                                            <td role="cell"><?= htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['type_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell">
                                                <?php 
                                                // Format the visit date and time
                                                $formattedDate = date('M j, Y', strtotime($row['visit_date']));
                                                $formattedTime = date('g:i A', strtotime($row['visit_time']));
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
                                                <?php if (!empty($row['requested_visit_date']) && !empty($row['requested_visit_time']) && $row['reschedule_status'] === 'pending'): ?>
                                                    <button 
                                                        class="btn btn-sm btn-success btn-action" 
                                                        onclick="openRequestModal('<?= $row['school_name'] ?>', '<?= $row['requested_visit_date'] ?>', '<?= $row['requested_visit_time'] ?>', <?= $row['id'] ?>)">
                                                        <i class="fa fa-exclamation-circle"></i> Request Received!
                                                    </button><br>
                                                <?php endif; ?>

                                                <button class="btn btn-sm btn-primary btn-action" 
                                                    onclick="openViewModal(
                                                        <?= $row['id']; ?>, 
                                                        <?= htmlspecialchars(json_encode($row['school_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['type_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['visit_date']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['visit_time']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['title']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['description']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['status']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['created_at']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['confirmed_at']), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <i class="fa fa-eye"></i>
                                                </button>

                                                <button class="btn btn-sm btn-warning btn-action" 
                                                    onclick="openEditModal(
                                                        <?= $row['id']; ?>, 
                                                        <?= htmlspecialchars(json_encode($row['school_id']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['visit_type_id']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['visit_date']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['visit_time']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['title']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['description']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['created_at']), ENT_QUOTES, 'UTF-8'); ?>)"
                                                    <?php if ($row['status'] !== 'pending') echo 'disabled'; ?>>
                                                    <i class="fa fa-edit"></i>
                                                </button>

                                                <button class="btn btn-sm btn-danger btn-action" 
                                                    onclick="openDeleteModal(
                                                        <?= $row['id']; ?>, 
                                                        <?= htmlspecialchars(json_encode($row['school_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['title']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['status'])); ?>
                                                    )">
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
                                    <th class="bg-light"><i class="fa fa-school"></i> Receiving School</th>
                                    <td id="viewSchool"></td>
                                </tr>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" role="dialog" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel"><i class="fa fa-edit"></i> Edit Visit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="editForm" action="functions/update-visit.php" method="POST" aria-labelledby="editModalLabel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="hidden" id="editSchool" name="school">
                                <label for="editSchool" class="form-label">Receiving School</label>

                                <select class="form-control" id="editSchool" name="school" disabled required>
                                <?php
                                // Fetch schools that have an active admin
                                $schoolQuery = "
                                    SELECT s.id, s.school_name
                                    FROM tbl_school s
                                    JOIN tbl_school_admin sa ON s.id = sa.school_id
                                    JOIN tbl_user u ON sa.user_id = u.id
                                    WHERE s.status = 'active' 
                                      AND s.isDeleted = 0
                                      AND u.account_status = 'active'
                                      AND u.isDeleted = 0
                                    GROUP BY s.id, s.school_name";

                                $schoolResult = $conn->query($schoolQuery);

                                // Populate school options
                                while ($school = $schoolResult->fetch_assoc()) {
                                    echo "<option value=\"{$school['id']}\">{$school['school_name']}</option>";
                                }
                                ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editType" class="form-label">Visit Type</label>
                                <select class="form-select" id="editType" name="type" required>
                                    <?php
                                    // Fetch visit types from the database
                                    $typeQuery = "SELECT id, type_name FROM tbl_visit_types";
                                    $typeResult = $conn->query($typeQuery);

                                    // Populate visit type options
                                    while ($type = $typeResult->fetch_assoc()) {
                                        echo "<option value=\"{$type['id']}\">{$type['type_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="editDate" class="form-label">Date of Visit</label>
                                <input type="date" class="form-control" id="editDate" name="date" required>
                            </div>

                            <div class="col-md-3">
                                <label for="editTime" class="form-label">Date of Time</label>
                                <input type="time" class="form-control" id="editTime" name="time" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="editTitle" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="editContent" class="form-label">Content</label>
                            <textarea class="form-control custom-textarea" id="editContent" name="content" rows="3" required></textarea>
                        </div>

                        <input type="hidden" id="editVisitId" name="visitId" value="">
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button form="editForm" type="submit" class="btn btn-primary">Save changes</button>
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
                    The visit details have been successfully updated.
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSuccessModalLabel"><i class="fa fa-trash"></i> Delete Visit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p>Are you sure you want to delete the <span id="deleteStatus"></span> visit entitled "<strong><span id="deleteTitle"></span></strong>" for <strong><span id="deleteSchool"></span></strong>?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
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
                    The visit has been successfully deleted.
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" role="dialog" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="requestModalLabel"><i class="fas fa-calendar"></i> Confirm Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p>
                        The school admin of <strong><span id="schoolName"></span></strong> 
                        submitted a request to reschedule the visit on 
                        <strong><span id="requestModalDetails"></span></strong>.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success">Confirm</button>
                    <button type="button" class="btn btn-danger">Deny</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" role="dialog" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header text-white" id="successModalHeader">
                    <h5 class="modal-title" id="successModalLabel"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Success message will be inserted here -->
                    <span id="successMessage"></span>
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
                    }
                });
            }

            // Function to open the edit modal and set values
            window.openEditModal = function(id, schoolId, typeId, visitDate, visitTime, title, description) { 
                document.getElementById('editVisitId').value = id;
                document.getElementById('editTitle').value = title;
                document.getElementById('editContent').value = description;
                document.getElementById('editSchool').value = schoolId;
                document.getElementById('editType').value = typeId;
                document.getElementById('editDate').value = visitDate;
                document.getElementById('editTime').value = visitTime;

                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();

                document.getElementById('editForm').onsubmit = function(event) {
                    event.preventDefault(); // Prevent default form submission
                    const formData = new FormData(this);

                    fetch('functions/update-visit.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'  // Ensure response is JSON
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            editModal.hide();
                            const successModal = new bootstrap.Modal(document.getElementById('editSuccessModal'));
                            successModal.show();
                            successModal._element.addEventListener('hidden.bs.modal', function() {
                                window.location.reload();
                            });
                        } else {
                            console.error('Error:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('There was an error submitting the form. Please try again.');
                    });
                };
            }
            
            window.openDeleteModal = function(id, schoolName, title, status) {
                document.getElementById('deleteSchool').textContent = schoolName;
                document.getElementById('deleteTitle').textContent = title;
                document.getElementById('deleteStatus').textContent = status;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();

                document.getElementById('confirmDeleteButton').onclick = function() {
                    fetch('functions/delete-visit.php', {
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
        });

        function openViewModal(id, schoolName, typeName, visitDate, visitTime, title, description, status, createdAt, confirmedAt) {
            document.getElementById('viewTitle').textContent = title;
            document.getElementById('viewSchool').textContent = schoolName;
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

    <script>
        // Function to format the date in "Month Day, Year" format
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-US', options); // e.g., December 25, 2024
        }

        // Function to format the time in 12-hour format with AM/PM
        function formatTime(timeStr) {
            const [hour, minute] = timeStr.split(':');
            const date = new Date(0, 0, 0, hour, minute); // Set hours and minutes
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }); // e.g., 1:00 PM
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Confirm button click handler
            document.querySelector('#requestModal .btn-success').addEventListener('click', () => {
                const visitId = document.getElementById('requestModal').getAttribute('data-visit-id');
                updateVisitRequest(visitId, 'confirm');
            });

            // Deny button click handler
            document.querySelector('#requestModal .btn-danger').addEventListener('click', () => {
                const visitId = document.getElementById('requestModal').getAttribute('data-visit-id');
                updateVisitRequest(visitId, 'deny');
            });
        });

        // Function to open the request modal and populate the fields
        function openRequestModal(schoolName, visitDate, visitTime, visitId) {
            // Format the visit date and time
            const formattedDate = formatDate(visitDate);
            const formattedTime = formatTime(visitTime);
            
            // Set the school name and requested schedule details
            document.getElementById('schoolName').textContent = schoolName;
            document.getElementById('requestModalDetails').textContent = `${formattedDate} at ${formattedTime}`;
            
            // Store the visit ID in the modal
            const requestModal = document.getElementById('requestModal');
            requestModal.setAttribute('data-visit-id', visitId);
            
            // Open the modal
            const modalInstance = new bootstrap.Modal(requestModal);
            modalInstance.show();
        }

        function updateVisitRequest(visitId, action) {
            fetch('functions/update-visit-reschedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ visit_id: visitId, action: action })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Set the success message based on the action
                    let successMessage = "";
                    let headerClass = "";

                    if (action === 'confirm') {
                        successMessage = "The requested schedule has been confirmed.";
                        headerClass = "bg-success";
                    } else if (action === 'deny') {
                        successMessage = "The requested schedule has been denied.";
                        headerClass = "bg-danger";
                    }

                    // Close the request modal first
                    const requestModal = document.getElementById('requestModal');
                    const requestModalInstance = bootstrap.Modal.getInstance(requestModal);
                    requestModalInstance.hide();

                    // Update success modal content
                    document.getElementById('successMessage').textContent = successMessage;
                    document.getElementById('successModalHeader').classList.add(headerClass);

                    // Show the success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();

                    // Reload page
                    document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
                        location.reload();
                    });
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the request.');
            });
        }
    </script>
</body>
</html>