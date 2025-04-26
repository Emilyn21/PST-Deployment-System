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
    <title>Add Subject Areas - Admin</title>
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
                    <h1 class="mt-5 h3" id="main-heading">Add Subject Areas</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Add Subject Areas</li>
                    </ol>
                    <section class="row">
                        <article class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <p class="mb-0">Fill in subject area details.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <form id="subjectAreaForm" action="functions/process-add-subject-area.php" method="POST" class="main-content">
                                        
                                        <!-- Subject Area Details Section -->
                                        <fieldset class="p-3 border rounded mb-4">
                                            <legend class="w-auto">Subject Area Details</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="subject-area-name" class="form-label">Subject Area Name:</label>
                                                    <input type="text" id="subject-area-name" name="subject-area-name" class="form-control" required placeholder="Enter subject area name">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="programs" class="form-label">Corresponding Programs:</label>
                                                    <select id="programs" name="programs[]" class="form-select" multiple required>
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
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="subject-area-description" class="form-label">Subject Area Description:</label>
                                                    <textarea id="subject-area-description" name="subject-area-description" class="form-control" rows="4" placeholder="Provide a brief description of the subject area" role="textbox"></textarea>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button Section -->
                                <div class="card-footer">
                                    <button form="subjectAreaForm" type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
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
                    <a href="manage-subject-area.php" class="btn btn-primary">View List</a>
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
            // Initialize Select2 for the grade programs dropdown
            $('#programs').select2({
                placeholder: "Select corresponding programs"
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('subjectAreaForm');

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
