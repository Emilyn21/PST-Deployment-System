<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['firstName'], $data['lastName'], $data['email'], $data['subjectArea'], $data['account_status'])) {
        $adviserId = $data['adviserId'];
        $firstName = isset($data['firstName']) ? strtoupper(trim($data['firstName'])) : '';
        $middleName = !empty($data['middleName']) ? strtoupper(trim($data['middleName'])) : null;
        $lastName = isset($data['lastName']) ? strtoupper(trim($data['lastName'])) : '';
        $email = $data['email'];
        $subjectArea = $data['subjectArea'];
        $accountStatus = $data['account_status'];

        $conn->begin_transaction();

        try {
            $sqlUser = "UPDATE tbl_user SET 
                            first_name = ?, 
                            middle_name = ?, 
                            last_name = ?, 
                            account_status = ?,
                            updated_at = NOW()
                        WHERE id = (SELECT user_id FROM tbl_adviser WHERE id = ?)";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("ssssi", $firstName, $middleName, $lastName, $accountStatus, $adviserId);

            $sqlAdviser = "UPDATE tbl_adviser SET 
                               subject_area_id = (SELECT id FROM tbl_subject_area WHERE id = ?) 
                           WHERE id = ?";
            $stmtAdviser = $conn->prepare($sqlAdviser);
            $stmtAdviser->bind_param("ii", $subjectArea, $adviserId);

            if ($stmtUser->execute() && $stmtAdviser->execute()) {
                $conn->commit();
                $response['status'] = 'success';
                $response['message'] = 'Adviser details updated successfully.';
            } else {
                $conn->rollback();
                $response['message'] = 'Failed to update adviser information. Please try again.';
            }

            $stmtUser->close();
            $stmtAdviser->close();
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

echo json_encode($response);
?>
