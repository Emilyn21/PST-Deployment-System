<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $attendance_id = $_POST['attendance_id'] ?? null;
    $content = trim($_POST['content'] ?? '');

    if (!$user_id || !$attendance_id || empty($content)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid input. Please fill out all required fields.'
        ]);
        exit;
    }

    // Check if an existing journal entry exists for the given attendance_id
    $checkQuery = "SELECT COUNT(*) AS count FROM tbl_journal WHERE attendance_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $attendance_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    $isUpdate = $checkRow['count'] > 0;
    $checkStmt->close();

    if ($isUpdate) {
        // Update the existing journal entry
        $query = "UPDATE tbl_journal SET content = ? WHERE attendance_id = ?";
    } else {
        // Insert a new journal entry
        $query = "INSERT INTO tbl_journal (attendance_id, content) VALUES (?, ?)";
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        exit;
    }

    if ($isUpdate) {
        $stmt->bind_param('si', $content, $attendance_id);
    } else {
        $stmt->bind_param('is', $attendance_id, $content);
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Journal entry saved successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save journal entry.']);
    }

    $stmt->close();
    exit;
}
?>
