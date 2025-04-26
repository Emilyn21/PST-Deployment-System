<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

// Check if it's a POST request and if the necessary data is available
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['program_id'], $_POST['major_name'], $_POST['major_abbreviation'], $_POST['major_description'], $_POST['status'])) {
        
        // Retrieve form data
        $id = $_POST['id'];
        $program_id = $_POST['program_id'];
        $major_name = $_POST['major_name'];
        $major_abbreviation = $_POST['major_abbreviation'];
        $major_description = empty(trim($_POST['major_description'])) ? null : $_POST['major_description'];
        $status = $_POST['status'];

        // Check for existing abbreviation
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_major WHERE (major_abbreviation = ? OR major_name = ? AND program_id = ?) AND id != ? AND status = 'active'");
        $checkStmt->bind_param("ssii", $major_abbreviation, $major_name, $program_id, $id);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            // Abbreviation already exists
            $response['status'] = 'error';
            $response['message'] = 'The major name and/or abbreviation already exists under the program. Please enter different values.';
        } else {
            // Prepare the SQL query to modify the program details
            $stmt = $conn->prepare("UPDATE tbl_major 
                    SET program_id = ?, 
                        major_name = ?, 
                        major_abbreviation = ?, 
                        major_description = ?, 
                        status = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?");

            // Bind the parameters to the prepared statement
            $stmt->bind_param("issssi", $program_id, $major_name, $major_abbreviation, $major_description, $status, $id);

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
