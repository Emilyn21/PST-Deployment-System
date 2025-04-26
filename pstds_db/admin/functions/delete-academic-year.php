<?php
include 'auth.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? $data['id'] : (isset($_POST['id']) ? $_POST['id'] : null);

    if ($id) {
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM tbl_pre_service_teacher pst
                 JOIN tbl_user u ON pst.user_id = u.id
                 WHERE pst.academic_year_id = ? AND u.isDeleted = 0) AS preServiceTeacherCount,
                (SELECT COUNT(*) FROM tbl_placement p
                 INNER JOIN tbl_pre_service_teacher pst ON p.pre_service_teacher_id = pst.id
                 WHERE pst.academic_year_id = ? AND p.isDeleted = 0 AND p.status = 'approved') AS approvedCount
        ");
        $stmt->bind_param("ii", $id, $id);
        $stmt->execute();
        $stmt->bind_result($preServiceTeacherCount, $approvedCount);
        $stmt->fetch();
        $stmt->close();

        if ($preServiceTeacherCount > 0 || $approvedCount > 0) {
            $teacherVerb = ($preServiceTeacherCount === 1) ? "is" : "are";
            $teacherNoun = ($preServiceTeacherCount === 1) ? "pre-service teacher" : "pre-service teachers";
            $placementNoun = ($approvedCount === 1) ? "placement" : "placements";

            if ($preServiceTeacherCount > 0 && $approvedCount === 0) {
                $response['status'] = 'error';
                $response['message'] = "The academic year cannot be deleted as there $teacherVerb $preServiceTeacherCount $teacherNoun currently registered under it.";
            } 
            elseif ($preServiceTeacherCount > 0 && $approvedCount > 0) {
                $response['status'] = 'error';
                $response['message'] = "The academic year cannot be deleted as there $teacherVerb $preServiceTeacherCount $teacherNoun currently registered under it, along with $approvedCount approved $placementNoun.";
            }
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("SELECT * FROM tbl_academic_year WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $academic_year = $result->fetch_assoc();

                $stmt = $conn->prepare("UPDATE tbl_academic_year SET isDeleted = 1 WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'The academic year has been deleted successfully.';
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
