<?php 
header("Content-Security-Policy: default-src 'self'; " . 
    "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; " . 
    "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " . 
    "img-src 'self' data:; " . 
    "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " . 
    "connect-src 'self';");

include 'includes/auth.php';

$stmt = $conn->prepare("SELECT DISTINCT
            tsa.id, 
            tu.first_name, 
            tu.middle_name, 
            tu.last_name, 
            CONCAT(tu.last_name, ', ', tu.first_name, 
            CASE 
                WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
                ELSE ''
            END) AS fullname,
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
            tu.email, 
            tu.profile_picture,
            tu.contact_number,
            ts.id AS school_id, 
            ts.school_name, 
            tu.street, 
            tu.barangay, 
            tu.city_municipality, 
            tu.province, 
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
            tu.account_status 
        FROM 
            tbl_school_admin tsa
        INNER JOIN 
            tbl_user tu ON tsa.user_id = tu.id
        INNER JOIN 
            tbl_school ts ON tsa.school_id = ts.id
        WHERE 
            tu.role = 'school_admin' 
            AND tu.isDeleted = 0
        ORDER BY 
            (CASE tU.account_status 
                WHEN 'active' THEN 1 
                WHEN 'inactive' THEN 2 
                ELSE 3 
            END),
            tu.last_name ASC");

$stmt->execute();
$result = $stmt->get_result();

// Fetching schools for the edit modal using prepared statement
$schoolQuery = "SELECT id, school_name FROM tbl_school WHERE status = ? AND isDeleted = ?";
$schoolStmt = $conn->prepare($schoolQuery);
$status = 'active';
$isDeleted = 0;
$schoolStmt->bind_param("si", $status, $isDeleted);
$schoolStmt->execute();
$schoolResult = $schoolStmt->get_result();

$schools = [];
if ($schoolResult->num_rows > 0) {
    while ($row = $schoolResult->fetch_assoc()) {
        $schools[] = $row;
    }
}

$schoolStmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Manage School Admins - Admin</title>
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
        th:nth-child(5), td:nth-child(5) {
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
                    <h1 class="mt-5 h3" id="main-heading">Manage School Admins</h1>
                    <ol class="breadcrumb mb_4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage School Admins</li>
                    </ol>

                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as total
                        FROM tbl_school_admin sa
                        JOIN tbl_user u ON sa.user_id = u.id
                        WHERE u.isDeleted = 0");
                    $stmt->execute();
                    $totalSchoolAdminsResult = $stmt->get_result();
                    $totalSchoolAdminsRow = $totalSchoolAdminsResult->fetch_assoc();
                    $totalSchoolAdmins = $totalSchoolAdminsRow['total'];

                    $stmt = $conn->prepare("SELECT COUNT(*) as total
                        FROM tbl_user
                        WHERE account_status = ? AND role = 'school_admin' AND isDeleted = 0");
                    $accountStatus = 'active';
                    $stmt->bind_param("s", $accountStatus);
                    $stmt->execute();
                    $activeSchoolAdminsResult = $stmt->get_result();
                    $activeSchoolAdminsRow = $activeSchoolAdminsResult->fetch_assoc();
                    $activeSchoolAdmins = $activeSchoolAdminsRow['total'];

                    $accountStatus = 'inactive';
                    $stmt->bind_param("s", $accountStatus);
                    $stmt->execute();
                    $inactiveSchoolAdminsResult = $stmt->get_result();
                    $inactiveSchoolAdminsRow = $inactiveSchoolAdminsResult->fetch_assoc();
                    $inactiveSchoolAdmins = $inactiveSchoolAdminsRow['total'];

                    $stmt->close();
                    ?>

                    <!-- Dashboard Summary Cards for School Admins -->
                    <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total School Admins">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie me-2"></i>
                                        <span>Total School Admins</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalSchoolAdmins ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Total Active School Admins">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-check me-2"></i>
                                        <span>Active School Admins</span>
                                    </div>
                                    <h3 class="mb-0"><?= $activeSchoolAdmins ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Total Inactive School Admins">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-slash me-2"></i>
                                        <span>Inactive School Admins</span>
                                    </div>
                                    <h3 class="mb-0"><?= $inactiveSchoolAdmins ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                View, edit, and manage details of school administrators, including their contact information, associated school, and status. Use the "Edit" or "Delete" buttons to modify or remove administrators.
                            </p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            School Admins
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV('List of Cooperating School Administrators.csv')" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel('List of Cooperating School Administrators.xlsx')" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="School Admins Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Name</th>
                                        <th scope="col" role="columnheader">Email Address</th>
                                        <th scope="col" role="columnheader">School</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()) : ?>
                                        <?php
                                        if (!empty($row['profile_picture'])) {
                                            $profilePictureBase64 = base64_encode($row['profile_picture']);
                                        } else {
                                            $profilePictureBase64 = null;
                                        }
                                        ?>
                                        <tr role="row">
                                            <td role="cell"><?= htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell">
                                                <?php
                                                $status = ucfirst($row['account_status']);
                                                $badgeClass = ($row['account_status'] === 'active') ? 'badge bg-success' : 'badge bg-danger';
                                                ?>
                                                <span class="<?= $badgeClass; ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </td>
                                            <td role="cell">
                                                <button class="btn btn-sm btn-info btn-action" 
                                                    onclick="openViewModal(
                                                        <?= $row['id']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['fullname']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['email']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['account_status']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($profilePictureBase64), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['address']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['contact_number']), ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['school_name']), ENT_QUOTES, 'UTF-8'); ?>)">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning btn-action" 
                                                    onclick="openEditModal(
                                                        <?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>,
                                                        <?= htmlspecialchars(json_encode($row['first_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['middle_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['last_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['email']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= $row['school_id']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['account_status']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action" 
                                                    onclick="openDeleteModal(
                                                        <?= $row['id']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['fml_name']), ENT_QUOTES, 'UTF-8'); ?>
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
    <div class="modal fade" id="viewModal" role="dialog" tabindex="-1" aria-labelledby="viewModalLabel" aria-modal="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header bg-info">
            <h5 class="modal-title" id="viewModalLabel">
              <i class="fa fa-eye"></i> View School Admin
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Profile Section -->
            <div class="d-flex align-items-center mb-3">
              <img id="viewProfilePicture" alt="Profile Picture" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
              <div class="ms-3">
                <h5 id="viewSchoolAdminName" class="mb-0"></h5>
                
                <!-- Account and Placement Status as badges -->
                <p id="viewStatus" class="mb-0"> 
                  <span id="viewAccountStatus" class="badge"></span>
                </p>
              </div>
            </div>

            <!-- Personal Information Card -->
            <h6 class="fw-bold"><i class="fa fa-user"></i> Personal Information</h6>
            <ul class="list-group mb-3">
              <li class="list-group-item"><i class="fa fa-envelope"></i> <strong>Email:</strong> <span id="viewEmail"></span></li>
              <li class="list-group-item"><i class="fa fa-map-marker-alt"></i> <strong>Address:</strong> <span id="viewAddress"></span></li>
              <li class="list-group-item"><i class="fa fa-phone"></i> <strong>Contact Number:</strong> <span id="viewContactNumber"></span></li>
            </ul>

            <!-- Academic Information Card -->
            <h6 class="fw-bold"><i class="fa fa-graduation-cap"></i> Academic Information</h6>
            <ul class="list-group mb-3">
              <li class="list-group-item"><i class="fa fa-school"></i> <strong>School:</strong> <span id="viewSchoolName"></span></li>
            </ul>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" role="dialog" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" aria-modal="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel"><i class="fa fa-edit"></i> Edit Adviser</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" action="functions/update-school-admin.php" method="POST" aria-labelledby="editModalLabel">
                        <input type="hidden" id="editSchoolAdminId" name="editSchoolAdminId">
                        <div class="mb-3">
                            <label for="editFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editFirstName" name="firstName" required aria-required="true" role="textbox">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editMiddleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="editMiddleName" name="middleName" role="textbox">
                            </div>
                            <div class="col-md-6">
                                <label for="editLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editLastName" name="lastName" required aria-required="true" role="textbox">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="editEmail" name="email" readonly disabled role="textbox">
                            <input type="hidden" id="editEmailHidden" name="email">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editSchool" class="form-label">School</label>
                                <select class="form-select" id="editSchool" name="school" required aria-required="true" aria-label="School" role="combobox">
                                    <?php foreach ($schools as $school): ?>
                                        <option value="<?= $school['id']; ?>"><?= $school['school_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="account_status" required aria-required="true" aria-label="Status" role="combobox">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
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

    <!-- Edit Error Modal -->
    <div class="modal fade" id="editErrorModal" tabindex="-1" role="dialog" aria-labelledby="editErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="editErrorModalLabel"><i class="fas fa-exclamation-circle"></i> Edit Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editErrorMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
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
                    The school administrator details have been successfully updated.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" role="dialog" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-trash"></i> Delete School Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Error Modal -->
    <div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-labelledby="deleteErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteErrorModalLabel"><i class="fas fa-exclamation-circle"></i> Delete Error</h5>
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

    <!-- Delete Success Modal -->
    <div class="modal fade" id="deleteSuccessModal" tabindex="-1" role="dialog" aria-labelledby="deleteSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSuccessModalLabel"><i class="fa fa-trash"></i> Delete Successful</h5>
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
                        noRows: "No cooperating school administrators have been added yet."
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

            window.openViewModal = function(id, schoolAdminName, email, accountStatus, profilePicture, address, contactNumber, schoolName) {
                // Check if profilePicture is valid (not null or empty)
                const profileImageSrc = profilePicture ? `data:image/jpeg;base64,${profilePicture}` : "../assets/img/default-image.jpg";

                // Set the values inside the modal
                document.getElementById('viewProfilePicture').src = profileImageSrc;
                document.getElementById('viewSchoolAdminName').textContent = schoolAdminName;
                document.getElementById('viewEmail').textContent = email;
                document.getElementById('viewAddress').textContent = address;
                document.getElementById('viewContactNumber').textContent = contactNumber;
                document.getElementById('viewSchoolName').textContent = schoolName;

                // Set the Account Status badge
                const accountBadge = document.getElementById('viewAccountStatus');
                const accountStatusClass = accountStatus === 'active' ? 'badge bg-success' :
                    accountStatus === 'inactive' ? 'badge bg-danger' : 'badge bg-secondary';
                const accountStatusText = accountStatus === 'active' ? 'Active' :
                    accountStatus === 'inactive' ? 'Inactive' : 'Unknown';

                accountBadge.className = accountStatusClass;
                accountBadge.textContent = accountStatusText;

                // Show the modal
                const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                viewModal.show();
            };

            window.openEditModal = function(schoolAdminId, firstName, middleName, lastName, email, schoolId, status) {
                document.getElementById('editSchoolAdminId').value = schoolAdminId;
                document.getElementById('editFirstName').value = firstName;
                document.getElementById('editMiddleName').value = middleName;
                document.getElementById('editLastName').value = lastName;
                document.getElementById('editEmail').value = email;
                document.getElementById('editEmailHidden').value = email;
                document.getElementById('editStatus').value = status;
                document.getElementById('editSchool').value = schoolId;

                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();

                document.getElementById('editForm').onsubmit = function(event) {
                    event.preventDefault();

                    const formData = Object.fromEntries(new FormData(this).entries());
                    formData.schoolAdminId = schoolAdminId;

                    fetch('functions/update-school-admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            editModal.hide();
                            const successModal = new bootstrap.Modal(document.getElementById('editSuccessModal'));
                            successModal.show();
                            successModal._element.addEventListener('hidden.bs.modal', function() {
                                window.location.reload();
                            });
                        } else {
                            editModal.hide();
                            document.getElementById('editErrorMessage').textContent = data.message;
                            const errorModal = new bootstrap.Modal(document.getElementById('editErrorModal'));
                            errorModal.show();
                        }
                    })
                    .catch(error => {
                        editModal.hide();
                        console.error('Error:', error);
                        document.getElementById('editErrorMessage').textContent = 'An unexpected error occurred. Please try again later.';
                        const errorModal = new bootstrap.Modal(document.getElementById('editErrorModal'));
                        errorModal.show();
                    });
                };
            };

            window.openDeleteModal = function(schoolAdminId, schoolAdminName) {
                document.getElementById('deleteName').textContent = schoolAdminName;
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();

                document.getElementById('confirmDeleteButton').onclick = function() {
                    fetch('functions/delete-school-admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ schoolAdminId: schoolAdminId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            deleteModal.hide();
                            const deleteSuccessModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'));
                            document.querySelector('#deleteSuccessModal .modal-body').textContent = data.message;
                            deleteSuccessModal.show();
                            deleteSuccessModal._element.addEventListener('hidden.bs.modal', function () {
                                window.location.reload();
                            });
                        } else {
                            deleteModal.hide();
                            const deleteErrorModal = new bootstrap.Modal(document.getElementById('deleteErrorModal'));
                            document.querySelector('#deleteErrorModal .modal-body').textContent = data.message;
                            deleteErrorModal.show();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        deleteModal.hide();
                        const deleteErrorModal = new bootstrap.Modal(document.getElementById('deleteErrorModal'));
                        document.querySelector('#deleteErrorModal .modal-body').textContent = 'An unexpected error occurred. Please try again later.';
                        deleteErrorModal.show();
                    });
                };
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

        function exportTableToCSV(filename) {
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["School Administrator Name", "Email Address", "School", "Status"];
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
            const csvFile = new Blob([csv], { type: 'text/csv' });
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

            table.querySelectorAll('thead th')[4].remove();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.cells[4].remove();
            }); 
            table.querySelector('thead').remove();

            const currentDate = new Date().toLocaleString();

            let win = window.open('', '_blank');
            if (!win) {
                alert("Pop-up blocked! Please allow pop-ups for this site.");
                return;
            }
            win.document.write('<html><head><title>List of School Administrators</title>');
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
            win.document.write('<div class="header-container"><p class="title-text">List of School Administrators</p></div>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Name</th><th>Email Address</th><th>School</th><th>Status</th><tr></thead>');
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
