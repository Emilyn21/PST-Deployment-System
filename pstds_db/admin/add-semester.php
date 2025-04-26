<?php 
include 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Add Semester - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
                    <h1 class="mt-5 h3" id="main-heading">Add Semesters</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add Semesters</li>
                    </ol>
                    <section class="row" role="region" aria-labelledby="section-heading">
                        <h2 id="section-heading" class="visually-hidden">Content Section</h2>
                        <article class="col-md-12" role="article">
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <p class="mb-0" role="note">Fill in semester details. If the academic year you are looking for is not listed in the dropdown, you may need to edit the academic year in <a href="manage-program.php" class="text-primary">Manage Academic Year</a> and update to active.
                                    </p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <!-- Semester Details Form -->
                                    <form id="semesterForm" action="functions/process-add-semester.php" method="POST" class="main-content" role="form">
                                        <!-- Semester Details Section -->
                                        <fieldset role="region" aria-labelledby="legend-semester-info" class="p-3 border rounded">
                                            <legend id="legend-semester-info" class="w-auto">Semester Details</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="academic-year" class="form-label">Academic Year:</label>
                                                    <select id="academic-year" name="academic_year" required class="form-select" role="combobox">
                                                        <option value="" disabled selected>Select academic year</option>
                                                        <?php
                                                        // Prepare the SQL query
                                                        $query = "SELECT id, academic_year_name FROM tbl_academic_year WHERE isDeleted = ?";
                                                        if ($stmt = $conn->prepare($query)) {
                                                            $isDeleted = 0;
                                                            $stmt->bind_param('i', $isDeleted);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            
                                                            if ($result->num_rows > 0) {
                                                                while ($row = $result->fetch_assoc()) {
                                                                    $academicYearId = htmlspecialchars($row['id']);
                                                                    $academicYearName = htmlspecialchars($row['academic_year_name']);

                                                                    // Fetch start_date and end_date for each academic year
                                                                    $subQuery = "SELECT start_date, end_date FROM tbl_academic_year WHERE id = ?";
                                                                    if ($subStmt = $conn->prepare($subQuery)) {
                                                                        $subStmt->bind_param('i', $academicYearId);
                                                                        $subStmt->execute();
                                                                        $subResult = $subStmt->get_result();
                                                                        if ($subResult->num_rows > 0) {
                                                                            $subRow = $subResult->fetch_assoc();
                                                                            $startDate = htmlspecialchars($subRow['start_date']);
                                                                            $endDate = htmlspecialchars($subRow['end_date']);
                                                                        }

                                                                        echo '<option value="' . $academicYearId . '" data-start-date="' . $startDate . '" data-end-date="' . $endDate . '">' . $academicYearName . '</option>';

                                                                        $subStmt->close();
                                                                    }
                                                                }

                                                            } else {
                                                                echo '<option value="">No active academic years available</option>';
                                                            }
                                                            
                                                            $stmt->close();
                                                        } else {
                                                            error_log('Database query preparation failed: ' . mysqli_error($conn));
                                                            echo '<option value="">Error fetching academic years</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="type" class="form-label">Type:</label>
                                                    <select id="type" name="type" required class="form-select" role="combobox">
                                                        <option value="" disabled selected>Select type</option>
                                                        <option value="first">FIRST</option>
                                                        <option value="second">SECOND</option>
                                                        <option value="midyear">MIDYEAR</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="start-date" class="form-label">Start Date:</label>
                                                    <input type="date" id="start-date" name="start_date" class="form-control" required role="input">
                                                    <div class="invalid-feedback">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="end-date" class="form-label">End Date:</label>
                                                    <input type="date" id="end-date" name="end_date" class="form-control" required role="input">
                                                    <div class="invalid-feedback">
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button -->
                                <div class="card-footer">
                                    <button form="semesterForm" type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                                </div>
                            </div>
                        </article>
                    </section>
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
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-semester.php" class="btn btn-primary">View List</a>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
    <script>
        // Function to show modal with message
        function showModal(modalId, message) {
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // Set the modal body message
            document.getElementById(modalId).querySelector('.modal-body').textContent = message;

            // If success modal, reload page after closing
            if (modalId === 'successModal') {
                document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
                    location.reload();
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('semesterForm');
            const academicYearSelect = document.getElementById('academic-year');
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');

            let academicYearStartDate = null;
            let academicYearEndDate = null;

            // Update academic year boundaries when a year is selected
            academicYearSelect.addEventListener('change', function () {
                const selectedOption = academicYearSelect.options[academicYearSelect.selectedIndex];
                academicYearStartDate = new Date(selectedOption.getAttribute('data-start-date'));
                academicYearEndDate = new Date(selectedOption.getAttribute('data-end-date'));
            });

            function validateDateRange() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);

                let isValid = true;

                startDateInput.classList.remove('is-invalid');
                endDateInput.classList.remove('is-invalid');

                const startFeedback = startDateInput.nextElementSibling;
                const endFeedback = endDateInput.nextElementSibling;

                if (!startDateInput.value) {
                    startDateInput.classList.add('is-invalid');
                    startFeedback.textContent = 'Please select a valid start date.';
                    isValid = false;
                } else if (academicYearStartDate && academicYearEndDate) {
                    if (startDate < academicYearStartDate || startDate > academicYearEndDate) {
                        startDateInput.classList.add('is-invalid');
                        startFeedback.textContent = `Start date must be on or after the academic year start date (${academicYearStartDate.toLocaleDateString()}).`;
                        isValid = false;
                    }
                }

                if (!endDateInput.value) {
                    endDateInput.classList.add('is-invalid');
                    endFeedback.textContent = 'Please select a valid end date.';
                    isValid = false;
                } else if (startDate > endDate) {
                    endDateInput.classList.add('is-invalid');
                    endFeedback.textContent = 'End date must be greater than or equal to the start date.';
                    isValid = false;
                } else if (academicYearStartDate && academicYearEndDate) {
                    if (endDate < academicYearStartDate || endDate > academicYearEndDate) {
                        endDateInput.classList.add('is-invalid');
                        endFeedback.textContent = `End date must be on or before the academic year end date (${academicYearEndDate.toLocaleDateString()}).`;
                        isValid = false;
                    }
                }

                return isValid;
            }

            startDateInput.addEventListener('input', validateDateRange);
            endDateInput.addEventListener('input', validateDateRange);
            academicYearSelect.addEventListener('input', validateDateRange);
            academicYearSelect.addEventListener('change', validateDateRange);

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (!validateDateRange()) {
                    return;
                }

                const formData = new FormData(form);

                fetch('functions/process-add-semester.php', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showModal('successModal', data.message); // Pass the modalId and message
                        } else {
                            showModal('errorModal', data.message); // Pass the modalId and message
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                    });
            });
        });
    </script>
</body>
</html>
