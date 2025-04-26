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
    <title>Add Academic Years - Admin</title>
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
                    <h1 class="mt-5 h3" role="main-heading">Add Academic Years</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add Academic Years</li>
                    </ol>
                    <section class="row" role="region" aria-labelledby="section-heading">
                        <h2 id="section-heading" class="visually-hidden">Content Section</h2>
                        <article class="col-md-12" role="article">
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <p class="mb-0" role="note">Fill in academic year details.</p>
                                </div>
                            </div>
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <!-- Academic Year Form -->
                                    <form id="academicYearForm" action="functions/process-add-academic-year.php" method="POST" class="main-content" role="form">
                                        <!-- Academic Year Details Section -->
                                        <fieldset role="region" aria-labelledby="legend-academic-year-info" class="p-3 border rounded">
                                            <legend id="legend-academic-year-info" class="w-auto">Academic Year Details</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="academic-year" class="form-label">Academic Year:</label>
                                                    <input type="text" id="academic-year" name="academic_year" class="form-control" required placeholder="e.g., 2024-2025" role="textbox">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="start-date" class="form-label">Start Date:</label>
                                                    <input type="date" id="start-date" name="start_date" class="form-control" required role="input">
                                                    <div class="invalid-feedback">
                                                        Please select a valid start date.
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="end-date" class="form-label">End Date:</label>
                                                    <input type="date" id="end-date" name="end_date" class="form-control" required role="input">
                                                    <div class="invalid-feedback">
                                                        Please select a valid end date (greater than start date).
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button -->
                                <div class="card-footer">
                                    <button form="academicYearForm" type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                                </div>
                            </div>
                        </article>
                    </section>
                </div>
            </main>
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
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-academic-year.php" class="btn btn-primary">View List</a>
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
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('academicYearForm');
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');

            function validateDateRange() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);

                startDateInput.classList.remove('is-invalid');
                endDateInput.classList.remove('is-invalid');

                if (!startDateInput.value || !endDateInput.value || startDate > endDate) {
                    if (!startDateInput.value) startDateInput.classList.add('is-invalid');
                    if (!endDateInput.value || startDate > endDate) endDateInput.classList.add('is-invalid');
                    return false;
                }
                return true;
            }

            startDateInput.addEventListener('input', validateDateRange);
            endDateInput.addEventListener('input', validateDateRange);

            form.addEventListener('submit', function (e) {
                if (!validateDateRange()) {
                    e.preventDefault();
                    return false;
                }
                
                e.preventDefault();
                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showModal(data);
                    } else {
                        showModal(data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showModal({ status: 'error', message: 'An unknown error occurred. Please try again.' });
                });
            });

            // Show the modal based on the response
            function showModal(response) {
                const modalId = response.status === 'success' ? 'successModal' : 'errorModal';
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();

                document.getElementById(modalId).querySelector('.modal-body').textContent = response.message;

                if (response.status === 'success') {
                    document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
                        resetForm();
                        location.reload();
                    });
                }
            }

            function resetForm() {
                form.reset();
            }
        });
    </script>
</body>
</html>
