<?php 
include 'includes/auth.php';

$stmt = $conn->prepare("SELECT id, title, content, announcement_type, created_at, audience, file_url
        FROM tbl_announcement
        WHERE isDeleted = 0
        ORDER BY created_at DESC");
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
    <title>Manage Announcements - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        @media (max-width: 576px) {
            .flex-wrap-nowrap {
                flex-wrap: wrap !important;
            }
        } .btn-action {
            margin-top: 0.2rem;
        } .form-group {
            margin-bottom: 0.15rem;
        }
        th:nth-child(5), td:nth-child(5) {
            white-space: nowrap;
            width: auto !important;
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
                    <h1 class="mt-5 h3" id="main-heading">Manage Announcements</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Announcements</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">Manage announcements.</p>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Announcements
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Announcements Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Title</th>
                                        <th scope="col" role="columnheader">Type</th>
                                        <th scope="col" role="columnheader">Audience</th>
                                        <th scope="col" role="columnheader">Date Created</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()) : ?>
                                        <tr role="row">
                                            <td role="cell"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars(ucfirst($row['announcement_type']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell">
                                                <?php 
                                                $audience = htmlspecialchars(ucfirst($row['audience']), ENT_QUOTES, 'UTF-8');

                                                if ($audience == 'Pre-service teacher') {
                                                    echo 'Pre-Service Teacher';
                                                } elseif ($audience == 'School_admin') {
                                                    echo 'School Administrators';
                                                } elseif ($audience == 'Cooperating teacher') {
                                                    echo 'Cooperating Teacher';
                                                } elseif ($audience == 'Adviser') {
                                                    echo 'Adviser';
                                                } else {
                                                    echo $audience;
                                                }
                                                ?>
                                            </td>
                                            <td role="cell">
                                                <?php 
                                                // Format created_at
                                                if (!empty($row['created_at'])) {
                                                    $formattedCreatedAt = date('M j, Y, g:i A', strtotime($row['created_at']));
                                                    echo htmlspecialchars($formattedCreatedAt, ENT_QUOTES, 'UTF-8'); // Formatted created_at
                                                } else {
                                                    echo htmlspecialchars('Not available', ENT_QUOTES, 'UTF-8'); // Default for empty created_at
                                                }
                                                ?>
                                            </td>
                                            <td role="cell">
                                                <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#viewModal"
                                                    onclick='openViewModal(
                                                        <?= $row['id']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['title']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['content']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode(ucfirst($row['announcement_type'])), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?php 
                                                        // Format created_at
                                                        if (!empty($row['created_at'])) {
                                                            $formattedCreatedAt = date('M j, Y, g:i A', strtotime($row['created_at']));
                                                            echo htmlspecialchars(json_encode($formattedCreatedAt), ENT_QUOTES, 'UTF-8'); // Formatted created_at
                                                        } else {
                                                            echo htmlspecialchars(json_encode('Not available'), ENT_QUOTES, 'UTF-8'); // Default for empty created_at
                                                        }
                                                        ?>, 
                                                        <?= htmlspecialchars(json_encode(ucfirst($row['audience'])), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['file_url']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )'>
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editModal"
                                                    onclick='openEditModal(
                                                        <?= $row['id']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['title']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['content']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['announcement_type']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?php 
                                                        // Format created_at
                                                        if (!empty($row['created_at'])) {
                                                            $formattedCreatedAt = date('M j, Y, g:i A', strtotime($row['created_at']));
                                                            echo htmlspecialchars(json_encode($formattedCreatedAt), ENT_QUOTES, 'UTF-8'); // Formatted created_at
                                                        } else {
                                                            echo htmlspecialchars(json_encode('Not available'), ENT_QUOTES, 'UTF-8'); // Default for empty created_at
                                                        }
                                                        ?>, 
                                                        <?= htmlspecialchars(json_encode($row['audience']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                        <?= htmlspecialchars(json_encode($row['file_url']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )'>
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    onclick='openDeleteModal(
                                                        <?= $row['id']; ?>,
                                                        <?= htmlspecialchars(json_encode($row['title']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )'>
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($result->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center" role="cell">No announcements have been added yet.</td>
                                        </tr>
                                    <?php endif; ?>
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
    <div class="modal fade" id="viewModal" role="dialog" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewModalLabel"><i class="fa fa-eye"></i> View Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="viewTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="viewTitle" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="viewAudience" class="form-label">Audience</label>
                            <input type="text" class="form-control" id="viewAudience" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="viewType" class="form-label">Type</label>
                            <input type="text" class="form-control" id="viewType" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="viewContent" class="form-label">Content</label>
                        <textarea class="form-control" id="viewContent" rows="5" readonly></textarea>
                    </div>
                    <div class="mb-3" id="viewAttachmentContainer">
                        <label for="viewAttachment" class="form-label">Attachment</label>
                        <div id="viewAttachment">No attachment available.</div>
                    </div>
                    <p><strong>Date Created: </strong><span id="viewDateCreated"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" role="dialog" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel"><i class="fa fa-edit"></i> Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAnnouncementForm" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="editTitle" name="title" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editAudience" class="form-label">Audience</label>
                                <select class="form-select" id="editAudience" name="audience" required>
                                    <option value="all">All</option>
                                    <option value="pre-service teacher">Pre-Service Teachers</option>
                                    <option value="adviser">Advisers</option>
                                    <option value="school_admin">School Administrators</option>
                                    <option value="cooperating teacher">Cooperating Teachers</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="editType" class="form-label">Type</label>
                                <select class="form-select" id="editType" name="type" required>
                                    <option value="general">General</option>
                                    <option value="reminder">Reminder</option>
                                    <option value="important">Important</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editContent" class="form-label">Content</label>
                            <textarea class="form-control" id="editContent" name="content" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editFile" class="form-label">Attachment</label>
                            <input type="file" class="form-control" id="editFile" name="file">
                        </div>
                        <div class="mb-3">
                            <span id="currentAttachmentText">No attachment available</span>
                            <span id="currentAttachmentLink" style="display:none;">
                                <a id="attachmentLink" href="#" target="_blank" rel="noopener noreferrer"></a>
                            </span>
                        </div>
                        <div class="mb-3">
                            <input type="checkbox" id="removeAttachment" name="removeAttachment">
                            <label for="removeAttachment" class="form-label">Remove current attachment</label>
                        </div>
                        <p><strong>Date Created: </strong><span id="editDateCreated"></span></p>
                        <input type="hidden" id="announcementId" name="announcementId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editAnnouncementForm" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Success Modal -->
    <div class="modal fade" id="editSuccessModal" role="dialog" tabindex="-1" aria-labelledby="editSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editSuccessModalLabel"><i class="fas fa-check-circle"></i> Edit Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The announcement details have been successfully updated.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" role="dialog" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-trash"></i> Delete Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the announcement: <strong id="deleteAnnouncementTitle"></strong>?
                </div>
                <div class="modal-footer">
                    <form id="deleteAnnouncementForm" method="POST">
                        <input type="hidden" id="deleteAnnouncementId" name="announcementId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Success Modal -->
    <div class="modal fade" id="deleteSuccessModal" role="dialog" tabindex="-1" aria-labelledby="deleteSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSuccessModalLabel">Delete Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The announcement has been successfully deleted.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new simpleDatatables.DataTable('#datatablesSimple');
        });

        function openViewModal(id, title, content, type, createdAt, audience, fileUrl) {
            document.getElementById('viewTitle').value = title;
            document.getElementById('viewContent').value = content;
            document.getElementById('viewType').value = type;
            document.getElementById('viewAudience').value = audience;
            document.getElementById('viewDateCreated').textContent = createdAt;

            // Handle attachment preview
            var attachmentView = document.getElementById('viewAttachment');
            if (fileUrl && fileUrl !== 'null') {
                var fileName = fileUrl.split('/').pop();
                
                var maxLength = 20;
                var truncatedFileName = fileName.length > maxLength 
                    ? fileName.substring(0, maxLength) + '...'
                    : fileName;
                
                attachmentView.innerHTML = `<a href="../uploads/a/${fileUrl}" target="_blank" title="${fileName}">${truncatedFileName}</a>`;
            } else {
                attachmentView.innerHTML = 'No attachment available.';
            }

            var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();

            document.getElementById('viewModal').addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('modal-open');
                document.querySelector('.modal-backdrop')?.remove();
                document.body.style.overflow = '';  // Ensure overflow is reset after modal closes
            });
        }

        function openEditModal(id, title, content, type, createdAt, audience, fileUrl) {
            document.getElementById('announcementId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editContent').value = content;
            document.getElementById('editType').value = type;
            document.getElementById('editAudience').value = audience;
            document.getElementById('editDateCreated').textContent = createdAt;

            var currentAttachmentText = document.getElementById('currentAttachmentText');
            var currentAttachmentLink = document.getElementById('currentAttachmentLink');
            var attachmentLink = document.getElementById('attachmentLink');

            if (fileUrl && fileUrl !== 'null') {
                var fileName = fileUrl.split('/').pop();
                var maxLength = 20;
                var truncatedFileName = fileName.length > maxLength 
                    ? fileName.substring(0, maxLength) + '...'
                    : fileName;

                currentAttachmentText.innerHTML = `Current attachment:`;
                attachmentLink.innerHTML = truncatedFileName;
                attachmentLink.href = `../uploads/a/${fileUrl}`;
                attachmentLink.setAttribute('title', fileName);
                currentAttachmentLink.style.display = 'inline';
            } else {
                currentAttachmentText.innerHTML = 'No attachment available.';
                currentAttachmentLink.style.display = 'none';
            }

            document.getElementById('removeAttachment').checked = false;

            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();

            document.getElementById('editAnnouncementForm').onsubmit = function (e) {
                e.preventDefault();

                var formData = new FormData(this);  // Collect form data, including files
                
                if (document.getElementById('removeAttachment').checked) {
                    formData.append('removeAttachment', true);
                }

                fetch('functions/update-announcement.php', {
                    method: 'POST',
                    body: formData  // FormData object, no need for headers
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        editModal.hide();

                        var successModal = new bootstrap.Modal(document.getElementById('editSuccessModal'), {
                            backdrop: 'static',  // Use static backdrop from editModal
                            keyboard: false
                        });
                        successModal.show();

                        // Ensure the backdrop remains until the success modal is closed
                        document.getElementById('editSuccessModal').addEventListener('hidden.bs.modal', function () {
                            document.querySelector('.modal-backdrop')?.remove();  // Remove backdrop after success modal is closed
                            location.reload();  // Reload the page after success
                        });
                    } else {
                        alert('Failed to update announcement.');
                    }
                })
                .catch(error => console.error('Error:', error));
            };

            document.getElementById('editModal').addEventListener('hidden.bs.modal', function () {
                // Only restore the backdrop if the success modal hasn't been shown yet
                if (!document.getElementById('editSuccessModal').classList.contains('show')) {
                    document.body.classList.add('modal-open');
                    document.querySelector('.modal-backdrop')?.remove();
                    document.body.style.overflow = '';
                }
            });
        }

        function openDeleteModal(id, title) {
            document.getElementById('deleteAnnouncementId').value = id;
            document.getElementById('deleteAnnouncementTitle').innerText = title;

            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();

            document.getElementById('deleteAnnouncementForm').onsubmit = function (e) {
                e.preventDefault();

                var formData = new FormData(this);
                fetch('functions/delete-announcement.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        deleteModal.hide();

                        var successModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'), {
                            backdrop: 'static',  // Use static backdrop from deleteModal
                            keyboard: false
                        });
                        successModal.show();

                        document.getElementById('deleteSuccessModal').addEventListener('hidden.bs.modal', function () {
                            document.querySelector('.modal-backdrop')?.remove();
                            location.reload();
                        });
                    } else {
                        alert('Failed to delete the announcement: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the announcement.');
                });
            };

            document.getElementById('deleteModal').addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('modal-open');
                document.querySelector('.modal-backdrop')?.remove();
                document.body.style.overflow = '';
            });
        }
    </script>
</body>
</html>