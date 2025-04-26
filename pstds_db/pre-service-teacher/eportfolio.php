<?php
include 'includes/auth.php';

$user_id = $_SESSION['user_id'];

// Step 1: Retrieve the full name of the logged-in user from tbl_user
$sql_user = "SELECT CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name 
             FROM tbl_user WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 1) {
    $user = $result_user->fetch_assoc();
    $full_name = $user['full_name'];

    // Step 2: Retrieve the pre_service_teacher_id from tbl_pre_service_teacher
    $sql_teacher = "SELECT id AS pre_service_teacher_id 
                    FROM tbl_pre_service_teacher WHERE user_id = ?";
    $stmt_teacher = $conn->prepare($sql_teacher);
    $stmt_teacher->bind_param("i", $user_id);
    $stmt_teacher->execute();
    $result_teacher = $stmt_teacher->get_result();

    if ($result_teacher->num_rows === 1) {
        $teacher = $result_teacher->fetch_assoc();
        $pre_service_teacher_id = $teacher['pre_service_teacher_id'];

        // Step 3: Check if the user has already uploaded an e-Portfolio
        $sql_eportfolio = "SELECT file_link 
                           FROM tbl_eportfolio 
                           WHERE pre_service_teacher_id = ?";
        $stmt_eportfolio = $conn->prepare($sql_eportfolio);
        $stmt_eportfolio->bind_param("i", $pre_service_teacher_id);
        $stmt_eportfolio->execute();
        $result_eportfolio = $stmt_eportfolio->get_result();

        if ($result_eportfolio->num_rows === 1) {
            $eportfolio = $result_eportfolio->fetch_assoc();
            $file_link = $eportfolio['file_link'];
            $file_uploaded = true; // Flag indicating a file already exists
        } else {
            $file_uploaded = false; // No file found
        }
    } else {
        echo "Pre-service teacher not found.";
        exit();
    }
} else {
    echo "User not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>e-Portfolio</title>

    <link href="../css/jsdelivr.style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        } .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 5px;
            padding: 20px;
            width: 100%;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        } .drop-zone:hover {
            border-color: #aaa;
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
                    <h1 class="mt-4">Add e-Portfolio</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Add e-Portfolio</li>
                    </ol>
                    <section class="row">
                        <article class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <?php if ($file_uploaded): ?>
                                        <p class="mb-0">You have already uploaded your e-Portfolio.</p>
                                        <p><strong>Uploaded File:</strong> <a href="<?= $file_link ?>" target="_blank">View File</a></p>
                                        <button class="btn btn-primary mt-2" id="remove-file-button">Remove Current File</button>
                                    <?php else: ?>
                                        <p class="mb-0">You can submit your e-Portfolio files below.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!$file_uploaded): ?>
                                <div class="drop-zone" id="drop-zone">
                                    <p>Drag and drop files here or click to upload</p>
                                    <button class="btn btn-primary" id="upload-button" style="width: 100%">Upload Files</button>
                                    <input type="file" id="file-input" multiple style="display: none;">
                                </div>
                                <ul class="file-list" id="file-list"></ul>
                                <button class="btn btn-success btn-submit" id="submit-button">Submit e-Portfolio</button>
                            <?php endif; ?>
                        </article>
                    </section>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Upload</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to upload this file as your final e-Portfolio? You won't be able to upload another.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancel-button">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmUpload">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove Confirmation Modal -->
    <div class="modal fade" id="removeModal" tabindex="-1" role="dialog" aria-labelledby="removeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="removeModalLabel">Confirm Removal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to remove your current e-Portfolio? You will be able to replace it with another one.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRemove">Remove</button>
                </div>
            </div>
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

    <!-- Notice Modal -->
    <div class="modal fade" id="noticeModal" role="dialog" tabindex="-1" aria-labelledby="noticeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="noticeModalLabel"><i class="fas fa-exclamation-circle"></i> Notice</h5>
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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('remove-file-button').addEventListener('click', function () {
            $('#removeModal').modal('show');
        });

        document.getElementById('confirmRemove').addEventListener('click', function () {
            // Send AJAX request to remove the file
            $.ajax({
                url: 'process-remove-eportfolio.php',
                method: 'POST',
                data: { user_id: <?= $user_id ?> },
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        showSuccess(data.message);
                    } else {
                        showError(data.message);
                    }
                    // Close the remove modal
                    $('#removeModal').modal('hide');
                },
                error: function () {
                    showError('An unexpected error occurred.');
                    $('#removeModal').modal('hide');
                }
            });
        });

        function showError(message) {
            $('#errorModal .modal-body').text(message);
            $('#errorModal').modal('show');
        }

        function showSuccess(message) {
            $('#successModal .modal-body').text(message);
            $('#successModal').modal('show');

            $('#successModal').on('hidden.bs.modal', function () {
                location.reload();
            });
        }
    </script>
    <script>
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const uploadButton = document.getElementById('upload-button');
        const submitButton = document.getElementById('submit-button');
        const confirmUploadButton = document.getElementById('confirmUpload');
        const fileList = document.getElementById('file-list');
        const errorModal = $('#errorModal');
        const successModal = $('#successModal');
        const errorModalBody = $('#errorModal .modal-body');
        const cancelButton = document.getElementById('cancel-button');
        let selectedFile = null;

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFileSelection(e.dataTransfer.files);
        });

        uploadButton.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => handleFileSelection(e.target.files));

        function handleFileSelection(files) {
            if (files.length > 1) {
                showError('Only one file can be uploaded.');
                return;
            }
            selectedFile = files[0];
            displayFiles([selectedFile]);
        }

        function displayFiles(files) {
            fileList.innerHTML = '';
            files.forEach(file => {
                const listItem = document.createElement('li');
                listItem.textContent = file.name;
                fileList.appendChild(listItem);
            });
            submitButton.disabled = false;
        }

        submitButton.addEventListener('click', () => {
            if (!selectedFile) {
                showNotice('Please select a file to upload.');
                return;
            }
            $('#confirmModal').modal('show');
        });

        confirmUploadButton.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('user_id', <?= $user_id ?>);

            $.ajax({
                url: 'process-upload-eportfolio.php',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: (response) => {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Show Success Modal
                        showSuccess(data.message);
                    } else {
                        showError(data.message);
                    }
                    $('#confirmModal').modal('hide');
                }
            });
        });

        cancelButton.addEventListener('click', () => {
            $('#confirmModal').modal('hide');
        });

        function showError(message) {
            errorModalBody.text(message);
            errorModal.modal('show');
        }

        function showSuccess(message) {
            $('#successModal .modal-body').text(message);
            successModal.modal('show');

            successModal.on('hidden.bs.modal', () => {
                location.reload();
            });
        }

        function showNotice(message) {
            $('#noticeModal .modal-body').text(message);
            $('#noticeModal').modal('show'); 
        }
    </script>
</body>
</html>