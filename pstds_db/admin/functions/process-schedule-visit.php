<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = $_POST['school'];
    $visit_type_id = $_POST['visit_type'];
    $date = $_POST['date'];
    $time = $_POST['time_slot'];
    $title = $_POST['title'];
    $description = $_POST['visit_details'];
    $created_by = $user_id;

    $conn->begin_transaction();

    try {
        $sql = "INSERT INTO tbl_visit 
                (title, description, visit_date, visit_time, visit_type_id, school_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssiii', $title, $description, $date, $time, $visit_type_id, $school_id, $created_by);

        if ($stmt->execute()) {
            $schoolAdminSql = "SELECT tu.id FROM tbl_school_admin tsa JOIN tbl_user tu ON tu.id = tsa.user_id WHERE tsa.school_id = ?";
            $stmtSchoolAdmin = $conn->prepare($schoolAdminSql);
            $stmtSchoolAdmin->bind_param("i", $school_id);
            $stmtSchoolAdmin->execute();
            $stmtSchoolAdmin->bind_result($school_admin_id);
            
            $schoolAdminIds = [];
            while ($stmtSchoolAdmin->fetch()) {
                $schoolAdminIds[] = $school_admin_id;
            }
            $stmtSchoolAdmin->close();
            
            $sqlVisitType = "SELECT type_name FROM tbl_visit_types WHERE id = ?";
            $stmtVisitType = $conn->prepare($sqlVisitType);
            $stmtVisitType->bind_param('i', $visit_type_id);
            $stmtVisitType->execute();
            $stmtVisitType->bind_result($visit_type_name);
            $stmtVisitType->fetch();
            $stmtVisitType->close();

            $visit_type_name = strtolower($visit_type_name);
            $formatted_date = date("M j, Y", strtotime($date));
            $formatted_time = date("g:i A", strtotime($time));

            if (!empty($schoolAdminIds)) {
                $sqlNotification = "INSERT INTO tbl_notification (user_id, message, link, type) 
                                    VALUES (?, ?, ?, 'alert')";
                $stmtNotification = $conn->prepare($sqlNotification);

                $message = "A {$visit_type_name} visit has been scheduled by the CvSU Teacher Education Department: 
                    '{$title}' on {$formatted_date} at {$formatted_time}. Please confirm or decline.";
                $link = "manage-visit.php";
                
                foreach ($schoolAdminIds as $schoolAdminId) {
                    $stmtNotification->bind_param("iss", $schoolAdminId, $message, $link);
                    $stmtNotification->execute();
                }

                $stmtNotification->close();
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'The visit has been scheduled successfully!']);
        } else {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error scheduling the visit. Please try again.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
