<?php 
include 'includes/auth.php';

// Fetch the active semester with the most recent start date
$semesterStmt = $conn->prepare("
    SELECT id, start_date, end_date 
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

    // Use the fetched semester ID to filter the main query
    $stmt = $conn->prepare("
        SELECT DISTINCT
            tpst.student_number, 
            tu.first_name, 
            tu.middle_name, 
            tu.last_name,
            CONCAT(tu.last_name, ', ', tu.first_name, 
            CASE 
                WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
                ELSE ''
            END) AS student_name,
            tu.email, 
            CONCAT(
            COALESCE(tp.program_abbreviation, 'N/A'),
                CASE 
                    WHEN tm.major_abbreviation IS NOT NULL THEN CONCAT('-', tm.major_abbreviation)
                    ELSE ''
                END
            ) AS program_major, 
            tpst.program_id, 
            tm.major_name AS major, 
            tpst.major_id, 
            CASE 
                WHEN COALESCE(tu.city_municipality, tu.province) IS NULL 
                THEN ''
                ELSE TRIM(
                    CONCAT(
                        COALESCE(tu.city_municipality, ''),
                        CASE WHEN tu.city_municipality IS NOT NULL AND tu.province IS NOT NULL THEN ', ' ELSE ' ' END,
                        COALESCE(tu.province, '')
                    )
                )
            END AS address,
            ta.academic_year_name,
            tpst.semester_id, 
            tu.account_status 
        FROM 
            tbl_pre_service_teacher tpst
        INNER JOIN 
            tbl_user tu ON tpst.user_id = tu.id
        INNER JOIN
            tbl_semester ts ON tpst.semester_id = ts.id
        INNER JOIN 
            tbl_academic_year ta ON ts.academic_year_id = ta.id
        INNER JOIN 
            tbl_program tp ON tpst.program_id = tp.id
        LEFT JOIN 
            tbl_major tm ON tpst.major_id = tm.id
        WHERE 
            tu.role = 'pre-service teacher' 
            AND tu.isDeleted = 0
            AND tu.account_status = 'active'
            AND tpst.placement_status = 'unplaced'
            AND tpst.semester_id = ?
        ORDER BY 
            CASE WHEN tu.street IS NOT NULL THEN 1 ELSE 2 END,
            CASE WHEN tu.barangay IS NOT NULL THEN 1 ELSE 2 END,
            CASE WHEN tu.city_municipality IS NOT NULL THEN 1 ELSE 2 END,
            CASE WHEN tu.province IS NOT NULL THEN 1 ELSE 2 END,
            tu.last_name ASC,
            tpst.created_at ASC
    ");

    $stmt->bind_param('i', $activeSemesterId); // Bind the semester ID
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
    <title>Place Pre-Service Teachers - Admin</title>
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
        } .slot-container {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        } .slot-container input {
            flex: 1;
        } .slot-container .btn-danger {
            margin-left: 10px;
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
                    <h1 class="mt-5 h3" id="main-heading">Place Pre-Service Teachers</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Place Pre-Service Teachers</li>
                    </ol>
                    <section class="row" role="region" aria-labelledby="section-heading">
                        <h2 id="section-heading" class="visually-hidden">Content Section</h2>
                        <article class="col-md-12" role="article">
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <p class="mb-0" role="note">Place pre-service teachers to schools by selecting a school from the dropdown, specifying the start and end dates for their placement, and adding pre-service teachers from the list below using the "Add Slot" button. Each pre-service teacher can only be added once.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <form id="assign-pre-service-form" action="functions/process-add-pst-placement.php" method="POST" class="main-content" role="form">
                                        <fieldset role="region" aria-labelledby="legend-placement-details" class="p-3 border rounded">
                                            <legend id="legend-placement-details" class="w-auto">Placement Details</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="school" class="form-label">School:</label>
                                                    <select id="school" name="school" required class="form-select">
                                                        <option value="" selected disabled>Select School</option>
                                                        <?php
                                                        // Fetch schools that have an active admin
                                                        $schoolQuery = "
                                                            SELECT ts.id, ts.school_name
                                                            FROM tbl_school ts
                                                            JOIN tbl_school_admin tsa ON ts.id = tsa.school_id
                                                            JOIN tbl_user tu ON tsa.user_id = tu.id
                                                            WHERE ts.status = 'active' 
                                                              AND ts.isDeleted = 0
                                                              AND tu.account_status = 'active'
                                                              AND tu.isDeleted = 0
                                                            GROUP BY ts.id, ts.school_name";

                                                        $schoolResult = $conn->query($schoolQuery);

                                                        // Populate school options
                                                        while ($school = $schoolResult->fetch_assoc()) {
                                                            echo "<option value=\"{$school['id']}\">{$school['school_name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="start-date" class="form-label">Start Date:</label>
                                                    <input type="date" id="start-date" name="start_date" required class="form-control">
                                                    <div class="invalid-feedback">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="end-date" class="form-label">End Date:</label>
                                                    <input type="date" id="end-date" name="end_date" required class="form-control">
                                                    <div class="invalid-feedback">
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>

                                        <fieldset role="region" aria-labelledby="legend-pre-service-teacher-slots" class="p-3 border rounded mt-4">
                                            <legend id="legend-pre-service-teacher-slots" class="w-auto">Pre-Service Teachers</legend>
                                            <div class="row mb-3" id="slots-container">
                                                <!-- Slots will be dynamically added here -->
                                            </div>
                                        </fieldset>

                                        <!-- Assign Button -->
                                        <div class="text-end mt-4">
                                            <button type="submit" class="btn btn-primary"><i class="fas fa-clipboard-check"></i> Assign</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </article>
                    </section>

                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Unplaced Pre-Service Teachers
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Student Table">
                                    <thead role="rowgroup">
                                        <tr role="row">
                                            <th scope="col" role="columnheader">Student Number</th>
                                            <th scope="col" role="columnheader">Name</th>
                                            <th scope="col" role="columnheader">Program</th>
                                            <th scope="col" role="columnheader">Address</th>
                                            <th scope="col" role="columnheader">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody role="rowgroup">
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td class="text-center" role="cell"><?= htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell">
                                                <button type="button" class="btn btn-sm btn-success btn-action add-to-slot" 
                                                    data-id="<?= htmlspecialchars(json_encode($row['student_number']), ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-name="<?= htmlspecialchars(json_encode($row['student_name']), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-academic-year-id="<?= htmlspecialchars(json_encode($row['semester_id']), ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-plus"></i> 
                                                    Add
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- End Data Table -->

                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
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
                <div class="modal-body" id="successMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-pst-placement.php" class="btn btn-primary">View List</a>
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

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const datatablesSimple = document.getElementById('datatablesSimple');
            const addedStudentIds = new Set();
            let dataTableInstance;

            if (datatablesSimple) {
                dataTableInstance = new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No unplaced pre-service teachers available."
                    }
                });
            }
            
            const slotsContainer = document.getElementById('slots-container');
            if (!slotsContainer) {
                console.error('slots-container not found');
                return;
            }

            const assignButton = document.querySelector('button[type="submit"]');
            let slotCount = 0;

            // Initially disable the Assign button
            assignButton.disabled = true;

            // Function to update the state of the Assign button
            function updateAssignButtonState() {
                assignButton.disabled = slotsContainer.children.length === 0;
            }

            // Function to refresh button states based on added student IDs
            function refreshAddSlotButtons() {
                // Get all rows (even if they are hidden)
                const rows = datatablesSimple.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const studentIdElement = row.querySelector('td:first-child');
                    const addButton = row.querySelector('.add-to-slot');
                    
                    if (studentIdElement && addButton) {
                        const studentId = studentIdElement.textContent.trim();
                        addButton.disabled = addedStudentIds.has(studentId);
                    }
                });
            }

            datatablesSimple.addEventListener('click', function (event) {
                if (event.target.classList.contains('add-to-slot')) {
                    const button = event.target;
                    const studentId = JSON.parse(button.dataset.id); // Decode JSON
                    const academicYearId = JSON.parse(button.dataset.academicYearId); // Decode JSON
                    const studentName = JSON.parse(button.dataset.name); // Decode JSON

                    if (addedStudentIds.has(studentId)) {
                        button.disabled = true;
                        return;
                    }

                    slotCount++;

                    // Safely handle the studentName with special characters
                    const sanitizedStudentName = encodeForHTML(studentName);
                    const slotDiv = `
                        <div class="col-md-4 slot-container">
                            <input type="hidden" name="pre_service_teacher_ids[]" value="${studentId}">
                            <input type="hidden" name="academic_year_ids[]" value="${academicYearId}">
                            <input type="text" id="pre-service-teacher-${slotCount}" name="pre_service_teacher[]" required class="form-control" value="${sanitizedStudentName}" readonly>
                            <button type="button" class="btn btn-danger remove-slot" data-id="${studentId}">X</button>
                        </div>`;
                    slotsContainer.insertAdjacentHTML('beforeend', slotDiv);

                    addedStudentIds.add(studentId);
                    button.disabled = true;

                    updateAssignButtonState();
                }
            });

            // Remove slot when remove button is clicked
            slotsContainer.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-slot')) {
                    const slotContainer = event.target.closest('.slot-container');
                    const studentId = event.target.dataset.id;

                    addedStudentIds.delete(studentId);
                    slotsContainer.removeChild(slotContainer);

                    updateAssignButtonState();
                    refreshAddSlotButtons();
                }
            });

            // Listen for DataTable page, search, or length changes to refresh button states
            if (dataTableInstance) {
                dataTableInstance.on('datatable.page', function () {
                    refreshAddSlotButtons();
                });

                dataTableInstance.on('datatable.search', function (query) {
                    refreshAddSlotButtons();
                });

                dataTableInstance.on('datatable.length', function () {
                    refreshAddSlotButtons();
                });
            }


            // Ensure that the button states are consistent on load and whenever the data changes
            refreshAddSlotButtons();

            // Function to observe changes in the table body
            function observeTableChanges() {
                const tableBody = datatablesSimple.querySelector('tbody');

                if (tableBody) {
                    // Create a MutationObserver to watch for changes in the table body
                    const observer = new MutationObserver(() => {
                        refreshAddSlotButtons(); // Call refreshAddSlotButtons whenever rows are modified
                    });

                    // Observe changes to child elements (e.g., rows being added/removed/updated)
                    observer.observe(tableBody, {
                        childList: true,
                        subtree: true,
                    });
                }
            }

            // Call this function after initializing the DataTable
            observeTableChanges();

            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');

            function validateDates() {

                let isValid = true;

                // Get the entered dates
                const startDateValue = startDateInput.value;
                const endDateValue = endDateInput.value;

                const today = new Date();
                today.setHours(0, 0, 0, 0); // Normalize to start of the day

                const startDate = new Date(startDateValue);
                const endDate = new Date(endDateValue);

                // Validate Start Date
                if (!startDateValue || startDate <= today) {
                    isValid = false;
                    startDateInput.classList.add('is-invalid');
                    startDateInput.nextElementSibling.textContent = "Start date must be a future date.";
                } else {
                    startDateInput.classList.remove('is-invalid');
                    startDateInput.nextElementSibling.textContent = "";
                }

                // Validate End Date
                if (!endDateValue || endDate <= startDate) {
                    isValid = false;
                    endDateInput.classList.add('is-invalid');
                    endDateInput.nextElementSibling.textContent = "End date must be after the start date.";
                } else {
                    endDateInput.classList.remove('is-invalid');
                    endDateInput.nextElementSibling.textContent = "";
                }

                return isValid;
            }

            startDateInput.addEventListener('change', function() {
                const startDate = startDateInput.value;
                endDateInput.min = startDate;

                updateAssignButtonState();
            });

            endDateInput.addEventListener('change', function() {
                updateAssignButtonState();
            });

            document.getElementById('start-date').addEventListener('input', validateDates);
            document.getElementById('end-date').addEventListener('input', validateDates);

            // Add event listener for form submission
            const assignForm = document.getElementById('assign-pre-service-form');
            assignForm.addEventListener('submit', function (event) {
                if (!validateDates()) {
                    event.preventDefault();
                }
                event.preventDefault();

                // Check if slots are added
                if (slotsContainer.children.length === 0) {
                    const errorModalBody = document.querySelector('#errorModal .modal-body');
                    errorModalBody.textContent = "Please add at least one pre-service teacher.";
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                    return;
                }

                // Proceed with form submission if validation passes
                const formData = new FormData(this);

                fetch("functions/process-add-pst-placement.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        slotsContainer.innerHTML = '';
                        
                        document.getElementById('successMessage').innerHTML = `
                            ${data.message || "Pre-service teachers have been assigned successfully!"}
                            You can <a href="assign-adviser.php">assign their adviser now</a> or do it later.
                        `;
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        successModal._element.addEventListener('hidden.bs.modal', function () {
                            window.location.reload();
                        });
                    } else {
                        document.querySelector('#errorModal .modal-body').innerText = data.message || "An unknown error occurred.";
                        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        errorModal.show();
                    }
                });
            });
        });

        function encodeForHTML(value) {
            const tempElement = document.createElement('div');
            tempElement.textContent = value;
            return tempElement.innerHTML;
        }

    </script>
</body>
</html>