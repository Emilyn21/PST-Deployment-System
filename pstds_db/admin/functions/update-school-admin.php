<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed. Please try again later.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['firstName'], $data['lastName'], $data['email'], $data['school'], $data['account_status'])) {
        $schoolAdminId = $data['schoolAdminId'];
        $firstName = isset($data['firstName']) ? strtoupper(trim($data['firstName'])) : '';
        $middleName = !empty($data['middleName']) ? strtoupper(trim($data['middleName'])) : null;
        $lastName = isset($data['lastName']) ? strtoupper(trim($data['lastName'])) : '';
        $email = $data['email'];
        $school = $data['school'];
        $accountStatus = $data['account_status'];

        $conn->begin_transaction();

        try {
            $sqlUser = "UPDATE tbl_user SET 
                            first_name = ?, 
                            middle_name = ?, 
                            last_name = ?, 
                            account_status = ?,
                            updated_at = NOW()
                        WHERE id = (SELECT user_id FROM tbl_school_admin WHERE id = ?)";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("ssssi", $firstName, $middleName, $lastName, $accountStatus, $schoolAdminId);

            $sqlAdmin = "UPDATE tbl_school_admin SET 
                            school_id = ? 
                         WHERE id = ?";
            $stmtAdmin = $conn->prepare($sqlAdmin);
            $stmtAdmin->bind_param("ii", $school, $schoolAdminId);

            if ($stmtUser->execute() && $stmtAdmin->execute()) {
                $conn->commit();
                $response['status'] = 'success';
                $response['message'] = 'School administrator details updated successfully.';
            } else {
                $conn->rollback();
                $response['message'] = 'Failed to update school administrator information. Please try again.';
            }

            $stmtUser->close();
            $stmtAdmin->close();
        } catch (Exception $e) {
            $conn->rollback(); 
            $response['message'] = 'An unexpected error occurred. Please try again.';
        }
    } else {
        $response['message'] = 'Some required fields are missing. Please check your input and try again.';
    }
} else {
    header('Location: ../index.php');
    exit();
}

// Return response as JSON
echo json_encode($response);
?>
