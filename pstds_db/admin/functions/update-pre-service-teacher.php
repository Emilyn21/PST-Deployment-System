<?php
include 'auth.php';

$response = array('status' => 'error', 'message' => 'Update failed.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (isset($data['studentNumber'], $data['oldStudentNumber'], $data['firstName'], $data['middleName'], $data['lastName'], $data['program'], $data['major'], $data['semester'], $data['account_status'])) {
        
        // Get the posted data
        $preServiceTeacherId = $data['preServiceTeacherId'];
        $studentNumber = $data['studentNumber'];
        $oldStudentNumber = $data['oldStudentNumber'];
        $firstName = isset($data['firstName']) ? strtoupper(trim($data['firstName'])) : '';
        $middleName = !empty($data['middleName']) ? strtoupper(trim($data['middleName'])) : null;
        $lastName = isset($data['lastName']) ? strtoupper(trim($data['lastName'])) : '';
        $program = $data['program'];
        $major = (isset($data['major']) && $data['major'] === 'null') ? null : $data['major'];
        $semester = $data['semester'];
        $account_status = $data['account_status'];

        $sqlCheck = "SELECT COUNT(*) FROM tbl_pre_service_teacher WHERE student_number = ? AND student_number != ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("ss", $studentNumber, $oldStudentNumber);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($count > 0) {
            $response['message'] = 'This student number is already registered. Please use a different student number.';
            echo json_encode($response);
            exit();
        }

        $conn->begin_transaction();

        try {
            $sqlUser = "UPDATE tbl_user SET 
                            first_name = ?, 
                            middle_name = ?, 
                            last_name = ?, 
                            account_status = ?, 
                            updated_at = NOW() 
                        WHERE id = (SELECT user_id FROM tbl_pre_service_teacher WHERE id = ?)";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("ssssi", $firstName, $middleName, $lastName, $account_status, $preServiceTeacherId);

            $sqlPreServiceTeacher = "UPDATE tbl_pre_service_teacher SET 
                             student_number = ?, 
                             program_id = ?, 
                             major_id = ?, 
                             semester_id = ? 
                             WHERE id = ?";
            $stmtPreServiceTeacher = $conn->prepare($sqlPreServiceTeacher);
            $stmtPreServiceTeacher->bind_param("siisi", $studentNumber, $program, $major, $semester, $preServiceTeacherId);

            if ($stmtUser->execute() && $stmtPreServiceTeacher->execute()) {
                $conn->commit();
                $response['status'] = 'success';
                $response['message'] = 'Pre-service teacher details updated successfully.';
            } else {
                $conn->rollback();
                $response['message'] = 'Failed to update pre-service teacher information. Please try again.';
            }

            $stmtUser->close();
            $stmtPreServiceTeacher->close();
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
