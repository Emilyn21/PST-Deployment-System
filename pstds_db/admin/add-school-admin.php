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
    <title>Add School Admins - Admin</title>
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
                    <h1 class="mt-5 h3" id="main-heading">Add School Admins</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add School Admins</li>
                    </ol>
                    <section class="row" role="region" aria-labelledby="section-heading">
                        <h2 id="section-heading" class="visually-hidden">Content Section</h2>
                        <article class="col-md-12" role="article">
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <p class="mb-0" role="note">Fill in school administrator details. Make sure to fill in email address correctly.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <form id="schoolAdminForm" action="functions/process-add-school-admin.php" method="POST" class="main-content" role="form">
                                        <!-- Personal Information Section -->
                                        <fieldset role="region" aria-labelledby="legend-personal-info" class="p-3 border rounded">
                                            <legend id="legend-personal-info" class="w-auto">Personal Information</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="first-name" class="form-label">First Name:</label>
                                                    <input type="text" id="first-name" name="first_name" required placeholder="Enter first name" class="form-control" role="textbox">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="middle-name" class="form-label">Middle Name:</label>
                                                    <input type="text" id="middle-name" name="middle_name" placeholder="Enter middle name" class="form-control" role="textbox">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="last-name" class="form-label">Last Name:</label>
                                                    <input type="text" id="last-name" name="last_name" required placeholder="Enter last name" class="form-control" role="textbox">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="email" class="form-label">Email Address:</label>
                                                    <input type="email" id="email" name="email" required placeholder="Enter email address" class="form-control" role="textbox">
                                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                                </div>
                                            </div>
                                        </fieldset>

                                        <!-- Academic Information Section -->
                                        <fieldset role="region" aria-labelledby="legend-academic-info" class="p-3 border rounded mt-4">
                                            <legend id="legend-academic-info" class="w-auto">Academic Information</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="school" class="form-label">School:</label>
                                                    <select id="school" name="school" required class="form-select" role="combobox">
                                                        <option value="" disabled selected>Select School</option>
                                                        <?php
                                                            include '../connect.php';
                                                            $query = "SELECT id, school_name FROM tbl_school WHERE status = 'active' AND isDeleted = ?";
                                                            $stmt = $conn->prepare($query);
                                                            $isDeleted = 0;
                                                            $stmt->bind_param("i", $isDeleted);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            while ($row = $result->fetch_assoc()) {
                                                                echo '<option value="' . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8') . '</option>';
                                                            }
                                                            $stmt->close();
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button -->
                                <div class="card-footer">
                                    <button type="submit" form="schoolAdminForm" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
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
                    <a href="manage-school-admin.php" class="btn btn-primary" role="button">View List</a>
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
            const form = document.getElementById('schoolAdminForm');
            const emailInput = document.getElementById('email');

            // Function to validate email
            function validateEmail(input) {
                const value = input.value;
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);  // Validates email format

                if (isValid) {
                    input.classList.remove('is-invalid');
                } else {
                    input.classList.add('is-invalid');
                }

                return isValid;
            }

            emailInput.addEventListener('input', () => {
                validateEmail(emailInput);
            });

            form.addEventListener('submit', function (e) {
                const isValidEmail = validateEmail(emailInput);

                if (!isValidEmail) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();

                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Raw Response:', data);

                    if (data.status === 'error') {
                        if (data.errors) {
                            data.errors.forEach(error => {
                                if (error.field === 'email') {
                                    emailInput.classList.add('is-invalid');
                                    const feedback = emailInput.nextElementSibling;
                                    if (feedback) {
                                        feedback.textContent = error.message;
                                    }
                                }
                            });
                        }
                    } else {
                        showModal(data);
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
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
                        location.reload(); // Reload after success modal closes
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
