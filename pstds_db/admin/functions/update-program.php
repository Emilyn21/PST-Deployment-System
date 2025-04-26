<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

// Check if it's a POST request and if the necessary data is available
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['name'], $_POST['abbreviation'], $_POST['description'], $_POST['status'])) {
        
        // Retrieve form data
        $id = $_POST['id'];
        $program_name = $_POST['name'];
        $program_abbreviation = $_POST['abbreviation'];
        $program_description = empty(trim($_POST['description'])) ? null : $_POST['description'];
        $status = $_POST['status'];

        // Check if the withMajor checkbox is checked (1 if checked, 0 if not)
        $withMajor = isset($_POST['withMajor']) ? 1 : 0;

        // Check for existing abbreviation
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_program WHERE (program_abbreviation = ? OR program_name = ?) AND id != ?");
        $checkStmt->bind_param("ssi", $program_abbreviation, $program_name, $id);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            // Abbreviation already exists
            $response['status'] = 'error';
            $response['message'] = 'The program name and/or abbreviation already exists in the database.';
        } else {
            // Prepare the SQL query to modify the program details
            $stmt = $conn->prepare("UPDATE tbl_program 
                    SET program_name = ?, 
                        program_abbreviation = ?,
                        program_description = ?,
                        status = ?, 
                        withMajor = ?, 
                        updated_at = NOW() 
                    WHERE id = ?");

            // Bind the parameters to the prepared statement
            $stmt->bind_param("ssssii", $program_name, $program_abbreviation, $program_description, $status, $withMajor, $id);

            // Execute the query
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'The program details have been successfully updated.';
            } else {
                // Log SQL errors for debugging
                $response['message'] = 'Error: ' . $stmt->error;
            }

            // Close statements
            $stmt->close();
        }
    } else {
        $response['message'] = 'Error: Missing form data.';
    }
} else {
    $response['message'] = 'Error: Invalid request method.';
}

// Return response as JSON
echo json_encode($response);

?>