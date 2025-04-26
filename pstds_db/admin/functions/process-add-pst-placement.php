<?php
include 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolId = $_POST['school'] ?? null;
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $preServiceTeacherNumbers = $_POST['pre_service_teacher_ids'] ?? [];
    $createdBy = $user_id;

    if (!$schoolId || !$startDate || !$endDate || !$createdBy) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
        exit();
    }

    if (empty($preServiceTeacherNumbers)) {
        echo json_encode(['status' => 'error', 'message' => 'At least one pre-service teacher must be assigned.']);
        exit();
    }

    if (strtotime($startDate) >= strtotime($endDate)) {
        echo json_encode(['status' => 'error', 'message' => 'Start date must be before end date.']);
        exit();
    }

    $conn->begin_transaction();

    try {
        $stmtLookup = $conn->prepare("SELECT tpst.id, tpst.user_id, ts.academic_year_id FROM tbl_pre_service_teacher tpst JOIN tbl_semester ts ON tpst.semester_id = ts.id WHERE student_number = ?");
        $stmtGetUser = $conn->prepare("SELECT first_name, middle_name, last_name FROM tbl_user WHERE id = ?");
        $stmtGetAcademicYear = $conn->prepare("SELECT start_date, end_date FROM tbl_academic_year WHERE id = ?");
        $stmtGetSchoolName = $conn->prepare("SELECT school_name FROM tbl_school WHERE id = ?");
        $stmtInsert = $conn->prepare("INSERT INTO tbl_placement (school_id, start_date, end_date, pre_service_teacher_id, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmtUpdateStatus = $conn->prepare("UPDATE tbl_pre_service_teacher SET placement_status = 'pending' WHERE id = ?");
        $stmtGetSchoolAdmins = $conn->prepare("SELECT user_id FROM tbl_school_admin WHERE school_id = ?");

        $invalidStudentNames = [];
        $successfulPlacements = 0;

        foreach ($preServiceTeacherNumbers as $studentNumber) {
            $stmtLookup->bind_param("s", $studentNumber);
            $stmtLookup->execute();
            $result = $stmtLookup->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $teacherId = $row['id'];
                $userId = $row['user_id'];
                $academicYearId = $row['academic_year_id'];

                $stmtGetUser->bind_param("i", $userId);
                $stmtGetUser->execute();
                $userResult = $stmtGetUser->get_result();
                $user = $userResult->fetch_assoc();
                $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];

                $stmtGetAcademicYear->bind_param("i", $academicYearId);
                $stmtGetAcademicYear->execute();
                $academicYearResult = $stmtGetAcademicYear->get_result();
                $academicYear = $academicYearResult->fetch_assoc();

                if ($academicYear) {
                    $academicYearStart = strtotime($academicYear['start_date']);
                    $academicYearEnd = strtotime($academicYear['end_date']);
                    $placementStart = strtotime($startDate);
                    $placementEnd = strtotime($endDate);

                    if ($placementStart < $academicYearStart || $placementEnd > $academicYearEnd) {
                        $invalidStudentNames[] = $fullName;
                        continue;
                    }
                } else {
                    $invalidStudentNames[] = $fullName;
                    continue;
                }

                // Fetch school name
                $stmtGetSchoolName->bind_param("i", $schoolId);
                $stmtGetSchoolName->execute();
                $schoolResult = $stmtGetSchoolName->get_result();
                $school = $schoolResult->fetch_assoc();
                $schoolName = $school ? $school['school_name'] : 'Unknown School';

                // Insert placement data
                $stmtInsert->bind_param("isssi", $schoolId, $startDate, $endDate, $teacherId, $createdBy);
                $stmtInsert->execute();

                // Update status to 'pending'
                $stmtUpdateStatus->bind_param("i", $teacherId);
                $stmtUpdateStatus->execute();

                // Add notification for the pre-service teacher
                $sqlNotification = "INSERT INTO tbl_notification (user_id, message, link, type) 
                                    VALUES (?, ?, ?, 'info')";
                $stmtNotification = $conn->prepare($sqlNotification);
                $message = "You have been placed at $schoolName. View your placement details.";
                $link = "index.php";

                // Bind and execute the notification insertion for the pre-service teacher
                $stmtNotification->bind_param("iss", $userId, $message, $link);
                $stmtNotification->execute();

                $successfulPlacements++; // Increment successful placements
            } else {
                $invalidStudentNames[] = $studentNumber;
            }
        }

        if (!empty($invalidStudentNames)) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Placement dates are invalid for the following students: ' . implode(', ', $invalidStudentNames)]);
            exit();
        }

        // Now notify school admins if there were successful placements
        if ($successfulPlacements > 0) {
            // Fetch all school admins
            $stmtGetSchoolAdmins->bind_param("i", $schoolId);
            $stmtGetSchoolAdmins->execute();
            $adminResult = $stmtGetSchoolAdmins->get_result();

            while ($admin = $adminResult->fetch_assoc()) {
                $adminUserId = $admin['user_id'];

                // Insert notification for each school admin
                if ($successfulPlacements == 1) {
                    $adminNotificationMessage = "There is 1 new placement request at your school. Please review.";
                } else {
                    $adminNotificationMessage = "There are $successfulPlacements new placement requests at your school. Please review.";
                }
                $adminLink = "manage-placement-request.php";  // Link to the placement request page

                $adminNotificationSql = "INSERT INTO tbl_notification (user_id, message, link, type) 
                                          VALUES (?, ?, ?, 'info')";
                $stmtAdminNotification = $conn->prepare($adminNotificationSql);
                $stmtAdminNotification->bind_param("iss", $adminUserId, $adminNotificationMessage, $adminLink);
                $stmtAdminNotification->execute();
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pre-service teachers assigned successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }

    // Close prepared statements
    $stmtLookup->close();
    $stmtInsert->close();
    $stmtUpdateStatus->close();
    $stmtGetSchoolAdmins->close();
    exit();
} else {
    header('Location: ../index.php');
    exit();
}
