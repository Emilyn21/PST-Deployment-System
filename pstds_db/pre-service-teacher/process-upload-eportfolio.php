<?php
include 'includes/auth.php';

$user_id = $_SESSION['user_id'];

// Step 1: Retrieve the pre_service_teacher_id
$sql_teacher = "SELECT id FROM tbl_pre_service_teacher WHERE user_id = ?";
$stmt_teacher = $conn->prepare($sql_teacher);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$result_teacher = $stmt_teacher->get_result();

if ($result_teacher->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'Pre-service teacher not found.']);
    exit();
}

$teacher = $result_teacher->fetch_assoc();
$pre_service_teacher_id = $teacher['id'];

// Step 2: Handle the file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload failed.']);
    exit();
}

$uploaded_file = $_FILES['file'];
$file_name = $uploaded_file['name'];
$file_tmp = $uploaded_file['tmp_name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$allowed_extensions = ['pdf', 'docx', 'pptx', 'zip'];

if (!in_array($file_ext, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
    exit();
}

$unique_file_name = uniqid('eportfolio_', true) . '.' . $file_ext;

$upload_dir = 'uploads/e/';
$target_file = $upload_dir . $unique_file_name; // full path for upload
$file_link = $unique_file_name; // only the filename to be saved in DB

if (!move_uploaded_file($file_tmp, $target_file)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    exit();
}


// Step 3: Insert the record
$current_date = date('Y-m-d');
$sql_insert = "INSERT INTO tbl_eportfolio (pre_service_teacher_id, file_link, submission_date) VALUES (?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("iss", $pre_service_teacher_id, $file_link, $current_date);

if ($stmt_insert->execute()) {
    echo json_encode(['success' => true, 'message' => 'File uploaded successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: Unable to save file info.']);
}

$stmt_insert->close();
$stmt_teacher->close();
$conn->close();
?>