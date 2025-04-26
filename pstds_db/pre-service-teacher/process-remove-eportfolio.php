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

// Step 2: Retrieve the current e-Portfolio file link
$sql_eportfolio = "SELECT file_link FROM tbl_eportfolio WHERE pre_service_teacher_id = ?";
$stmt_eportfolio = $conn->prepare($sql_eportfolio);
$stmt_eportfolio->bind_param("i", $pre_service_teacher_id);
$stmt_eportfolio->execute();
$result_eportfolio = $stmt_eportfolio->get_result();

if ($result_eportfolio->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'No e-Portfolio found for this user.']);
    exit();
}

$eportfolio = $result_eportfolio->fetch_assoc();
$file_link = $eportfolio['file_link']; // ← You need this line!

$full_file_path = 'uploads/e/' . $file_link;
if (file_exists($full_file_path)) {
    if (!unlink($full_file_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete the file from the server.']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File does not exist on the server.']);
    exit();
}



// Step 4: Delete the record from the database
$sql_delete = "DELETE FROM tbl_eportfolio WHERE pre_service_teacher_id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $pre_service_teacher_id);

if ($stmt_delete->execute()) {
    echo json_encode(['success' => true, 'message' => 'e-Portfolio removed successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete e-Portfolio record from the database.']);
}

// Close the statements and connection
$stmt_teacher->close();
$stmt_eportfolio->close();
$stmt_delete->close();
$conn->close();
?>
