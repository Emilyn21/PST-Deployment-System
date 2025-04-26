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
    <title>Add Announcement - Admin</title>
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
                    <h1 class="mt-5 h3" id="main-heading">Add Announcement</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add Announcement</li>
                    </ol>
                    <section class="row">
                        <article class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <p class="mb-0">Fill in announcement details.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <form id="addAnnouncementForm" method="POST" enctype="multipart/form-data" class="main-content" role="form">
                                        <!-- Announcement Information Section -->
                                        <fieldset role="region" aria-labelledby="legend-announcement-info" class="p-3 border rounded">
                                            <legend id="legend-announcement-info" class="w-auto">Announcement Information</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="title" class="form-label">Title:</label>
                                                    <input type="text" id="title" name="title" required placeholder="Enter announcement title" class="form-control" role="textbox">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="audience" class="form-label">Audience:</label>
                                                    <select id="audience" name="audience[]" required class="form-select" role="combobox">
                                                        <option value="all">All</option>
                                                        <option value="pre-service teacher">Pre-Service Teachers</option>
                                                        <option value="adviser">Advisers</option>
                                                        <option value="school_admin">School Administrators</option>
                                                        <option value="cooperating teacher">Cooperating Teachers</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="type" class="form-label">Type:</label>
                                                    <select id="type" name="type" required class="form-select" role="combobox">
                                                        <option value="general">General</option>
                                                        <option value="reminder">Reminder</option>
                                                        <option value="important">Important</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="announcement-content" class="form-label">Content:</label>
                                                    <textarea id="announcement-content" name="content" placeholder="Enter announcement content" class="form-control" rows="5" required role="textbox"></textarea>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="file" class="form-label">Attachment (Optional):</label>
                                                    <input type="file" id="file" name="file" role="upload" aria-label="Upload file attachment" accept=".jpg,.png,.pdf" class="form-control">
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>

                                <!-- Submit Button -->
                                <div class="card-footer">
                                    <button form="addAnnouncementForm" type="submit" class="btn btn-primary"><i class="fas fa-bullhorn"></i> Post</button>
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
                    The announcement has been posted successfully!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-announcement.php" class="btn btn-primary">View List</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        document.getElementById('addAnnouncementForm').addEventListener('submit', function(event) {
            event.preventDefault();

            var formData = new FormData(this);

            fetch('functions/process-add-announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();

                    successModal._element.addEventListener('hidden.bs.modal', function () {
                        window.location.reload();
                    });
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>
