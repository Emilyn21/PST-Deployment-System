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
    <title>Add Programs - Admin</title>
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
                    <h1 class="mt-5 h3" id="main-heading">Add Programs</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add Programs</li>
                    </ol>
                    <section class="row" role="region" aria-labelledby="section-heading">
                        <h2 id="section-heading" class="visually-hidden">Content Section</h2>
                        <article class="col-md-12" role="article">
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <p class="mb-0" role="note">Fill in program details.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <!-- Program Details Form -->
                                    <form id="programForm" action="functions/process-add-program.php" method="POST" class="main-content" role="form">
                                        <!-- Program Details Section -->
                                        <fieldset role="region" aria-labelledby="legend-program-info" class="p-3 border rounded">
                                            <legend id="legend-program-info" class="w-auto">Program Details</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="program-abbreviation" class="form-label">Program Abbreviation:</label>
                                                    <input type="text" id="program-abbreviation" name="program_abbreviation" class="form-control" required placeholder="Enter abbreviation" role="textbox">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="program-name" class="form-label">Program Name:</label>
                                                    <input type="text" id="program-name" name="program_name" class="form-control" required placeholder="Enter program name" role="textbox">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="program-description" class="form-label">Program Description:</label>
                                                    <textarea id="program-description" name="program_description" class="form-control" rows="4" placeholder="Provide a brief description of the program" role="textbox"></textarea>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4 d-flex align-items-center">
                                                    <input type="checkbox" id="withMajor" name="withMajor" value="1" class="form-check-input me-2" role="checkbox">
                                                    <label for="withMajor" class="form-check-label">This program has a major</label>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button -->
                                <div class="card-footer">
                                    <button type="submit" form="programForm" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-program.php" class="btn btn-primary" role="button">View List</a>
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
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('programForm');

            form.addEventListener('submit', function (e) {
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
