<?php
include 'auth.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    $semesterId = isset($data['semesterId']) ? $data['semesterId'] : null;

    if ($semesterId) {
        // Check if there are pre-service teachers registered under the semester
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS preServiceTeacherCount 
            FROM tbl_pre_service_teacher tpst 
            JOIN tbl_user tu ON tpst.user_id = tu.id
            WHERE tpst.semester_id = ? AND tu.isDeleted = 0
        ");
        $stmt->bind_param("i", $semesterId);
        $stmt->execute();
        $stmt->bind_result($preServiceTeacherCount);
        $stmt->fetch();
        $stmt->close();

        if ($preServiceTeacherCount > 0) {
            $teacherVerb = ($preServiceTeacherCount === 1) ? "is" : "are";
            $teacherNoun = ($preServiceTeacherCount === 1) ? "pre-service teacher" : "pre-service teachers";

            $response['status'] = 'error';
            $response['message'] = "The semester cannot be deleted as there $teacherVerb $preServiceTeacherCount $teacherNoun registered to it.";
        } else {
            // Proceed to delete the semester
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("SELECT * FROM tbl_semester WHERE id = ?");
                $stmt->bind_param("i", $semesterId);
                $stmt->execute();
                $result = $stmt->get_result();
                $semester = $result->fetch_assoc();

                $stmt = $conn->prepare("
                    UPDATE tbl_semester 
                    SET isDeleted = 1, status = 'inactive' 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $semesterId);

                if ($stmt->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'The semester has been deleted successfully.';
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
        $response['message'] = 'Invalid request.';
    }
} else {
    header('Location: ../index.php');
    exit();
}

echo json_encode($response);
mysqli_close($conn);
?>
