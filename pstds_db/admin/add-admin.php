<?php 
include 'includes/auth.php';

// Check if success message is set in the session
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

// Check if error message is set in the session
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message after displaying
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
    <title>Add Admins - Admin</title>
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
    <?php include '../connect.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Add Admins</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Add Admins</li>
                    </ol>
                    <section class="row">
                        <article class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <p class="mb-0">Fill in admin details. Double check the email address.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <form action="functions/process-add-admin.php" method="POST" class="main-content">
                                        
                                        <!-- Personal Information Section -->
                                        <fieldset class="p-3 border rounded mb-4">
                                            <legend class="w-auto">Personal Information</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="first-name" class="form-label">First Name:</label>
                                                    <input type="text" id="first-name" name="first_name" class="form-control" required placeholder="Enter first name">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="middle-name" class="form-label">Middle Name:</label>
                                                    <input type="text" id="middle-name" name="middle_name" class="form-control" placeholder="Enter middle name">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="last-name" class="form-label">Last Name:</label>
                                                    <input type="text" id="last-name" name="last_name" class="form-control" required placeholder="Enter last name">
                                                </div>
                                            </div>
                                        </fieldset>
                                        
                                        <!-- Account Details Section -->
                                        <fieldset class="p-3 border rounded mb-4">
                                            <legend class="w-auto">Account Details</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="email" class="form-label">Email Address:</label>
                                                    <input type="email" id="email" name="email" class="form-control" required placeholder="Enter email address">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="password" class="form-label">Password:</label>
                                                    <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
                                                </div>
                                            </div>
                                        </fieldset>

                                        <!-- Submit Button Section -->
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
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
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <?php if ($success_message): ?>
                        <?php echo $success_message; ?>
                    <?php else: ?>
                        The admin has been added successfully!
                    <?php endif; ?>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-admin.php" class="btn btn-primary">View List</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" role="dialog" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <?php if ($error_message): ?>
                        <?php echo $error_message; ?>
                    <?php else: ?>
                        An unknown error occurred. Please try again.
                    <?php endif; ?>
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
        // Show success modal if the message is set
        <?php if (!empty($success_message)): ?>
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        <?php endif; ?>

        // Show error modal if the message is set
        document.addEventListener("DOMContentLoaded", function () {
            <?php if ($error_message): ?>
            var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
            <?php endif; ?>
        });
    </script>
</body>
</html>
