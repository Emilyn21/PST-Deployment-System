<?php
include 'auth.php';

header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'Update failed.');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the necessary data is available and not empty
    $requiredFields = ['id', 'type', 'acad_year_id', 'start_date', 'end_date', 'status'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $response['message'] = 'All fields are required.';
            echo json_encode($response);
            exit;
        }
    }

    // Retrieve and sanitize form data
    $id = $_POST['id'];
    $type = $_POST['type'];
    $acad_year_id = $_POST['acad_year_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = strtolower(trim($_POST['status']));

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Check if the same type already exists in the same academic year
        $uniqueCheckStmt = $conn->prepare("SELECT COUNT(*) 
                                           FROM tbl_semester 
                                           WHERE type = ? 
                                           AND academic_year_id = ? 
                                           AND id != ?");
        $uniqueCheckStmt->bind_param("sii", $type, $acad_year_id, $id);
        $uniqueCheckStmt->execute();
        $uniqueCheckStmt->bind_result($uniqueCount);
        $uniqueCheckStmt->fetch();
        $uniqueCheckStmt->close();

        if ($uniqueCount > 0) {
            throw new Exception('The selected type already exists for the given academic year.');
        }

        // Check for date overlaps within the same academic year
        $overlapCheckStmt = $conn->prepare("SELECT COUNT(*) 
                                            FROM tbl_semester 
                                            WHERE academic_year_id = ? 
                                            AND id != ? 
                                            AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) 
                                                 OR (? BETWEEN start_date AND end_date) 
                                                 OR (? BETWEEN start_date AND end_date))");
        $overlapCheckStmt->bind_param("iissssss", $acad_year_id, $id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
        $overlapCheckStmt->execute();
        $overlapCheckStmt->bind_result($overlapCount);
        $overlapCheckStmt->fetch();
        $overlapCheckStmt->close();

        if ($overlapCount > 0) {
            throw new Exception('The semester dates overlap with an existing semester in the same academic year.');
        }

        // Update semester details
        $stmt = $conn->prepare("UPDATE tbl_semester 
                                SET type = ?, 
                                    academic_year_id = ?, 
                                    start_date = ?, 
                                    end_date = ?, 
                                    status = ? 
                                WHERE id = ?");
        $stmt->bind_param("sisssi", $type, $acad_year_id, $start_date, $end_date, $status, $id);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'The semester details have been successfully updated.';
        } else {
            throw new Exception('Error: ' . $stmt->error);
        }

        // Commit the transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback the transaction if there's any error
        $conn->rollback();
        $response['message'] = $e->getMessage();
    } finally {
        $stmt->close();
    }
} else {
    $response['message'] = 'Error: Invalid request method.';
}

echo json_encode($response);
?>
