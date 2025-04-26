<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $program = trim($_POST['program_name']);
    $programAbbrv = trim($_POST['program_abbreviation']);
    $programDesc = $_POST['program_description'];
    $withMajor = isset($_POST['withMajor']) ? 1 : 0;

    $checkProgram = $conn->prepare("SELECT program_name, program_abbreviation FROM tbl_program WHERE program_name = ? OR program_abbreviation = ?");
    $checkProgram->bind_param("ss", $program, $programAbbrv);
    $checkProgram->execute();
    $result = $checkProgram->get_result();

    $duplicateName = false;
    $duplicateAbbrv = false;

    while ($row = $result->fetch_assoc()) {
        if ($row['program_name'] === $program) {
            $duplicateName = true;
        }
        if ($row['program_abbreviation'] === $programAbbrv) {
            $duplicateAbbrv = true;
        }
    }

    if ($duplicateName && $duplicateAbbrv) {
        $errorMessage = 'The program abbreviation "' . htmlspecialchars($programAbbrv) . '" and program name "' . htmlspecialchars($program) . '" are already registered.';
    } elseif ($duplicateName) {
        $errorMessage = 'The program name "' . htmlspecialchars($program) . '" is already registered.';
    } elseif ($duplicateAbbrv) {
        $errorMessage = 'The program abbreviation "' . htmlspecialchars($programAbbrv) . '" is already registered.';
    }

    if (!empty($errorMessage)) {
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        exit();
    }

    $insertProgramQuery = $conn->prepare("INSERT INTO tbl_program (program_name, program_abbreviation, program_description, withMajor) VALUES (?, ?, ?, ?)");
    $insertProgramQuery->bind_param("sssi", $program, $programAbbrv, $programDesc, $withMajor);
    if ($insertProgramQuery->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Program added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add program. Please try again.']);
    }

    $insertProgramQuery->close();
    $checkProgram->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
