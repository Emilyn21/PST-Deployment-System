<?php
include 'auth.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $programId = isset($data['programId']) ? $data['programId'] : (isset($_POST['programId']) ? $_POST['programId'] : null);

    if ($programId) {
        $errorMessages = [];
        $majorCount = 0;
        $preServiceTeacherCount = 0;

        $stmt = $conn->prepare("SELECT withMajor FROM tbl_program WHERE id = ?");
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $stmt->bind_result($withMajor);
        $stmt->fetch();
        $stmt->close();

        if ($withMajor === 1) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS majorCount 
                FROM tbl_major 
                WHERE program_id = ? AND isDeleted = 0
            ");
            $stmt->bind_param("i", $programId);
            $stmt->execute();
            $stmt->bind_result($majorCount);
            $stmt->fetch();
            $stmt->close();

            if ($majorCount > 0) {
                $majorNoun = ($majorCount === 1) ? "major" : "majors";
                $errorMessages[] = "it has $majorCount $majorNoun currently registered under it";
            }
        }

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS preServiceTeacherCount 
            FROM tbl_pre_service_teacher tpst 
            JOIN tbl_user tu ON tpst.user_id = tu.id
            WHERE tpst.program_id = ? AND tu.isDeleted = 0
        ");
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $stmt->bind_result($preServiceTeacherCount);
        $stmt->fetch();
        $stmt->close();

        if ($preServiceTeacherCount > 0) {
            $teacherVerb = ($preServiceTeacherCount === 1) ? "is" : "are";
            $teacherNoun = ($preServiceTeacherCount === 1) ? "pre-service teacher" : "pre-service teachers";
            $errorMessages[] = "there $teacherVerb $preServiceTeacherCount $teacherNoun registered to it";
        }

        if (!empty($errorMessages)) {
            $response['status'] = 'error';
            $response['message'] = "The program cannot be deleted as " . implode(" and ", $errorMessages) . ".";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("
                    UPDATE tbl_program 
                    SET isDeleted = 1, status = 'inactive' 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $programId);

                if ($stmt->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'The program has been deleted successfully.';
                } else {
                    $conn->rollback();
                    $response['status'] = 'error';
                    $response['message'] = 'Error: ' . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $response['status'] = 'error';
                $response['message'] = 'An error occurred during deletion.';
            }
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Invalid request. Program ID is missing.';
    }
} else {
    header('Location: ../index.php');
    exit();
}

echo json_encode($response);
mysqli_close($conn);
?>
