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
    <title>Add Cooperating Schools - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-5 h3" id="main-heading">Add Cooperating Schools</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Add Cooperating Schools</li>
                    </ol>
                    <section class="row">
                        <article class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <p class="mb-0">Fill in cooperating school details.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <form id="schoolForm" action="functions/process-add-cooperating-school.php" method="POST" class="main-content">
                                        
                                        <!-- School Details Section -->
                                        <fieldset class="p-3 border rounded mb-4">
                                            <legend class="w-auto">School Details</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="school-name" class="form-label">School Name:</label>
                                                    <input type="text" id="school-name" name="school_name" class="form-control" required placeholder="Enter school name">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="short-name" class="form-label">Short Name (Abbreviation):</label>
                                                    <input type="text" id="short-name" name="short_name" class="form-control" required placeholder="Enter short name">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="school-type" class="form-label">School Type:</label>
                                                    <select id="school-type" name="school_type" class="form-select" required>
                                                        <option value="" disabled selected>Select school type</option>
                                                        <option value="public">Public</option>
                                                        <option value="private">Private</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="levels" class="form-label">Levels:</label>
                                                    <select id="levels" name="grade_levels[]" class="form-select" multiple required>
                                                        <option value="Daycare">Daycare</option>
                                                        <option value="Kindergarten">Kindergarten</option>
                                                        <option value="Preschool">Preschool</option>
                                                        <option value="Elementary">Elementary</option>
                                                        <option value="Junior High School">Junior High School</option>
                                                        <option value="Senior High School">Senior High School</option>
                                                        <option value="ALS">Alternative Learning System</option>
                                                        <option value="Special Education">Special Education</option>
                                                        <option value="Montessori">Montessori Education</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </fieldset>

                                        <!-- School Address Section -->
                                        <fieldset class="p-3 border rounded mb-4">
                                            <legend class="w-auto">School Address</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="street" class="form-label">Street:</label>
                                                    <input type="text" id="street" name="street" class="form-control" placeholder="Enter street">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="barangay" class="form-label">Barangay:</label>
                                                    <input type="text" id="barangay" name="barangay" class="form-control" required placeholder="Enter barangay">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="municipality" class="form-label">Municipality/City:</label>
                                                    <input type="text" id="municipality" name="municipality" class="form-control" required placeholder="Enter municipality/city">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="province" class="form-label">Province:</label>
                                                    <input type="text" id="province" name="province" class="form-control" required placeholder="Enter province">
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button Section -->
                                <div class="card-footer">
                                    <button form="schoolForm" type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
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
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
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
                    <a href="manage-cooperating-school.php" class="btn btn-primary">View List</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for the grade levels dropdown
            $('#levels').select2({
                placeholder: "Select grade levels"
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('schoolForm');

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Determine which modal to show
                    const modalId = data.status === 'success' ? 'successModal' : 'errorModal';
                    showModal(modalId, data.message);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showModal('errorModal', 'An unknown error occurred. Please try again.');
                });
            });

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
        });
    </script>
</body>
</html>
