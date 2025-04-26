<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['subject-area-name'], $_POST['subject-area-description'], $_POST['programs'], $_POST['status'])) {
        
        $id = $_POST['id'];
        $name = $_POST['subject-area-name'];
        $description = !empty($_POST['subject-area-description']) ? trim($_POST['subject-area-description']) : null;
        $programs = implode(', ', $_POST['programs']);
        $status = $_POST['status'];

        // Begin transaction
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("UPDATE tbl_subject_area 
                    SET subject_area_name = ?, 
                        subject_area_description = ?, 
                        related_program = ?, 
                        status = ? 
                    WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $description, $programs, $status, $id);

            if ($stmt->execute()) {
                $conn->commit();
                $response['status'] = 'success';
                $response['message'] = 'The school details have been successfully updated.';
            } else {
                $conn->rollback();
                $response['message'] = 'An error occurred while updating the school details. Please try again later.';
            }

            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Something went wrong during the update process. Please try again later.';
        }
    } else {
        $response['message'] = 'Please fill in all the required fields.';
    }
} else {
    header('Location: ../index.php');
    exit();
}

// Close the database connection
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
