<?php 
include 'includes/auth.php';

$stmt = $conn->prepare("SELECT id, subject_area_name, subject_area_description, related_program, status
                        FROM tbl_subject_area 
                        WHERE isDeleted = 0
                    ");
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
    <title>Manage Subject Areas - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
        .select2-container--default .select2-dropdown {
            z-index: 9999; /* Ensure dropdown appears on top */
            width: 100%; /* Allow the dropdown to fit within its container */
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
                    <h1 class="mt-5 h3" id="main-heading">Manage Subject Areas</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Subject Areas</li>
                    </ol>
                    <?php
                    function getSchoolCount($conn, $condition) {
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_subject_area WHERE isDeleted = 0 AND $condition");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stmt->close();
                        return $row['total'] ?? 0;
                    }

                    $totalSubjectAreas = getSchoolCount($conn, "1 = 1");
                    $activeSubjectAreas = getSchoolCount($conn, "status = 'active'");
                    $inactiveSubjectAreas = getSchoolCount($conn, "status = 'inactive'");
                    ?>
                    <div class="row">
                        <!-- Total Subject Areas -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-primary text-white mb-4" role="region" aria-label="Total Subject Areas">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-school me-2"></i>
                                        <span>Total Subject Areas</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalSubjectAreas ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Active Subject Areas -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-success text-white mb-4" role="region" aria-label="Active Subject Areas">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span>Active Subject Areas</span>
                                    </div>
                                    <h3 class="mb-0"><?= $activeSubjectAreas ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Inactive Subject Areas -->
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-warning text-white mb-4" role="region" aria-label="Inactive Subject Areas">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <span>Inactive Subject Areas</span>
                                    </div>
                                    <h3 class="mb-0"><?= $inactiveSubjectAreas ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                Manage subject sreas and generate reports.
                            </p>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Subject Areas
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV('List of Subject Areas<.csv')" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel('List of Subject Areas<.xlsx')" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Subject Areas Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Subject Area Name</th>
                                        <th scope="col" role="columnheader">Corresponding Programs</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr role="row">
                                        <td role="cell"><?= htmlspecialchars($row['subject_area_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['related_program'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell">
                                            <?php
                                            $status = ucfirst($row['status']);
                                            $badgeClass = ($row['status'] === 'active') ? 'badge bg-success' : 'badge bg-danger';
                                            ?>
                                            <span class="<?= $badgeClass; ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td role="cell">
                                            <button class="btn btn-sm btn-info btn-action" 
                                                onclick="openViewModal(
                                                    <?= $row['id']; ?>,
                                                    <?= htmlspecialchars(json_encode($row['subject_area_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['subject_area_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['related_program']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                )">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning btn-action" 
                                                onclick="openEditModal(
                                                    <?= $row['id']; ?>,
                                                    <?= htmlspecialchars(json_encode($row['subject_area_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['subject_area_description']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['related_program']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                )">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action" 
                                                onclick="openDeleteModal(
                                                    <?= $row['id']; ?>,
                                                    <?= htmlspecialchars(json_encode($row['subject_area_name']), ENT_QUOTES, 'UTF-8'); ?>
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
                        <i class="fa fa-eye"></i> Subject Area Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-school"></i> Subject Area</th>
                                    <td id="viewSubjectArea"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-file-alt"></i> Description</th>
                                    <td id="viewDescription"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-calendar-alt"></i> Corresponding Programs</th>
                                    <td id="viewPrograms"></td>
                                </tr>
                                <tr>
                                    <th class="bg-light"><i class="fa fa-info-circle"></i> Status</th>
                                    <td>
                                        <span id="viewStatus" class="badge"></span>
                                    </td>
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
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit"></i> Edit Subject Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" action="functions/update-subject_area.php" method="POST">
                        <input type="hidden" id="editId" name="id">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label for="editName" class="form-label">Subject Area Name</label>
                                <input type="text" class="form-control" id="editName" name="subject-area-name" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="subject-area-description"></textarea>
                        </div>
                        <div class="row mb-3">     
                            <div class="col-sm-6">
                                <label for="editPrograms" class="col-form-label">Corresponding Programs</label>
                                <select id="editPrograms" name="programs[]" class="form-select" multiple required>
                                    <?php
                                    // Fetch programs with their majors (if any)
                                    $query = "
                                        SELECT 
                                            CONCAT(p.program_abbreviation, 
                                                   IFNULL(CONCAT('-', m.major_name), '')) AS program_with_major, 
                                            CONCAT(p.program_abbreviation, 
                                                   IFNULL(CONCAT('-', m.major_abbreviation), '')) AS program_with_major_value
                                        FROM 
                                            tbl_program p
                                        LEFT JOIN 
                                            tbl_major m ON p.id = m.program_id AND m.status = 'active'
                                        WHERE 
                                            p.status = 'active'
                                        ORDER BY
                                            program_with_major ASC
                                    ";

                                    $result = $conn->query($query);

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . htmlspecialchars($row['program_with_major_value']) . '">' . htmlspecialchars($row['program_with_major']) . '</option>';
                                        }
                                    } else {
                                        echo '<option value="" disabled>No programs found</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button form="editForm" type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Success Modal -->
    <div class="modal fade" id="editSuccessModal" role="dialog" tabindex="-1" aria-labelledby="editSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editSuccessModalLabel"><i class="fas fa-check-circle"></i> Edit Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The subject area details have been successfully updated.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-trash"></i> Delete Subject Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalBodyMessage">
                        Are you sure you want to delete <strong id='deleteName'></strong>?
                    </p>
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
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSuccessModalLabel"><i class="fa fa-trash"></i> Delete Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The subject area has been successfully deleted.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Error Modal -->
    <div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-labelledby="deleteErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteErrorModalLabel"><i class="fas fa-times"></i> Delete Unsuccessful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The subject area can't be deleted. There are registered administrators and related placements.
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../js/xlsx.full.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                const dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No subject areas have been added yet."
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

            window.openEditModal = function(id, name, description, programs, status) {
                document.getElementById('editId').value = id;
                document.getElementById('editName').value = name;
                document.getElementById('editDescription').value = description;

                // Set the grade levels in the Select2 dropdown
                $('#editPrograms').val(programs.split(', ')).trigger('change');

                // Dynamically set the selected option for the status
                let statusSelect = document.getElementById('editStatus');
                statusSelect.value = status.toLowerCase();

                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();

                // Handle form submission and trigger the success modal upon saving changes
                document.getElementById('editForm').onsubmit = function(event) {
                    event.preventDefault();
                    const formData = new FormData(this);

                    fetch('functions/update-subject-area.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        }
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
                            const errorModal = new bootstrap.Modal(document.getElementById('editErrorModal'));
                            document.querySelector('#editErrorModal .modal-body').innerText = data.message;
                            errorModal.show();
                            console.error('Error:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                };
            };

            window.openDeleteModal = function (id, name) {
                console.log("Opening delete modal for ID:", id, "Name:", name);

                // Set modal content
                document.getElementById('deleteName').innerText = name;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();

                document.getElementById('confirmDeleteButton').onclick = function () {
                    console.log("Deleting ID:", id);

                    fetch('functions/delete-subject-area.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id }),
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json(); // Try to parse JSON response
                        })
                        .then((data) => {
                            if (data.status === 'success') {
                                console.log(data.message);
                                deleteModal.hide();
                                const deleteSuccessModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'));
                                deleteSuccessModal.show();
                                deleteSuccessModal._element.addEventListener('hidden.bs.modal', function () {
                                    window.location.reload();
                                });
                            } else {
                                console.error('Error:', data.message);
                                deleteModal.hide();
                                const deleteErrorModal = new bootstrap.Modal(document.getElementById('deleteErrorModal'));
                                document.querySelector('#deleteErrorModal .modal-body').innerText = data.message;
                                deleteErrorModal.show();
                            }
                        })
                        .catch((error) => {
                            console.error('Non-JSON Error or Fetch Error:', error);
                        });

                };
            };
        });

        $(document).ready(function() {
            $('#editPrograms').select2({
                placeholder: "Select corresponding programs"
            });  // This should log the Select2 object if initialized correctly
        });

        function openViewModal(id, name, description, programs, status) {
            document.getElementById('viewSubjectArea').textContent = name;
            document.getElementById('viewDescription').textContent = description;
            document.getElementById('viewPrograms').textContent = programs;
            document.getElementById('viewStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);

            // Update status badge dynamically with date if confirmed/denied
            const statusElement = document.getElementById('viewStatus');
            statusElement.className = 'badge'; // Reset class

            if (status === 'active') {
                statusElement.classList.add('bg-success'); // Green badge
            } else if (status === 'inactive') {
                statusElement.classList.add('bg-danger'); // Red badge
            } else {
                statusElement.classList.add('bg-warning'); // Yellow badge
            }

            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
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

        function exportTableToCSV(filename) {
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Subject Area", "Corresponding Programs", "Status"];
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

            table.querySelectorAll('thead th')[3].remove();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.cells[3].remove();
            }); 
            table.querySelector('thead').remove();

            const currentDate = new Date().toLocaleString();

            const win = window.open('', '_blank');
            win.document.write('<html><head><title>List of Subject Areas</title>');
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
            win.document.write('<div class="header-container"><p class="title-text">List of Subject Areas</p></div>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Name</th><th>Corresponding Programs</th><th>Status</th><tr></thead>');
            win.document.write('<tbody>');

            table.querySelectorAll('tbody tr').forEach(row => {
                let cells = Array.from(row.cells);
                win.document.write('<tr>' + cells.map(cell => `<td>${cell.innerText}</td>`).join('') + '</tr>');
            });

            win.document.write('</tbody></table>');
            // Footer with date and time
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