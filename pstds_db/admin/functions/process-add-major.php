<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $major = htmlspecialchars($_POST['major_name']);
    $majorAbbrv = htmlspecialchars($_POST['major_abbreviation']);
    $majorDesc = htmlspecialchars($_POST['major_description']);
    $program = intval($_POST['program']);

    $checkMajor = $conn->prepare("SELECT major_name, major_abbreviation FROM tbl_major WHERE (major_name = ? OR major_abbreviation = ?) AND program_id = ?");
    $checkMajor->bind_param("ssi", $major, $majorAbbrv, $program);
    $checkMajor->execute();
    $result = $checkMajor->get_result();

    $duplicateName = false;
    $duplicateAbbrv = false;

    while ($row = $result->fetch_assoc()) {
        if ($row['major_name'] === $major) {
            $duplicateName = true;
        }
        if ($row['major_abbreviation'] === $majorAbbrv) {
            $duplicateAbbrv = true;
        }
    }

    if ($duplicateName && $duplicateAbbrv) {
        $errorMessage = 'The major abbreviation "' . htmlspecialchars($majorAbbrv) . '" and major name "' . htmlspecialchars($major) . '" are already registered for the selected program.';
    } elseif ($duplicateName) {
        $errorMessage = 'The major name "' . htmlspecialchars($major) . '" is already registered for the selected program.';
    } elseif ($duplicateAbbrv) {
        $errorMessage = 'The major abbreviation "' . htmlspecialchars($majorAbbrv) . '" is already registered for the selected program.';
    }

    if (!empty($errorMessage)) {
        echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        exit();
    } else {
        $insertMajorQuery = $conn->prepare("INSERT INTO tbl_major (major_name, major_abbreviation, major_description, program_id) VALUES (?, ?, ?, ?)");
        $insertMajorQuery->bind_param("sssi", $major, $majorAbbrv, $majorDesc, $program);

        if ($insertMajorQuery->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Major added successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add major. Please try again.']);
        }
        $insertMajorQuery->close();
    }

    $checkMajor->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

?>
