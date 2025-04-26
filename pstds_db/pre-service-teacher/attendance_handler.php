<?php
include '../connect.php';  // Database connection file

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);


$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['action'])) {
    $pre_service_teacher_id = $data['pre_service_teacher_id'];
    $placement_id = $data['placement_id'];

    // Server time zone setup (replace with your time zone)
    date_default_timezone_set('Asia/Manila');
    $current_date = date('Y-m-d');  // Get the current date
    $server_datetime = date('Y-m-d H:i:s');  // Get the current date and time

    if ($data['action'] === 'check_status') {
        // Query to check if the user has a time_in for today
        $sql_check = "SELECT time_in, time_out FROM tbl_attendance 
                      WHERE pre_service_teacher_id = ? 
                      AND placement_id = ? 
                      AND DATE(time_in) = ? 
                      ORDER BY time_in DESC LIMIT 1";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iis", $pre_service_teacher_id, $placement_id, $current_date);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
    
        if ($result_check->num_rows > 0) {
            $attendance = $result_check->fetch_assoc();
            if ($attendance['time_out'] === null) {
                // User has timed in but not timed out
                echo json_encode([
                    'success' => true, 
                    'status' => 'time_in', 
                    'time_in' => $attendance['time_in']
                ]);
            } else {
                // User has both time in and time out recorded
                echo json_encode([
                    'success' => true, 
                    'status' => 'time_out', 
                    'time_in' => $attendance['time_in'], 
                    'time_out' => $attendance['time_out']
                ]);
            }
        } else {
            // No attendance record for today
            echo json_encode(['success' => true, 'status' => 'none']);
        }
        exit();
    }
    

    // Conditional bypass for checking if already logged in today
    $disable_once_a_day_limit = true;  // Set to true to bypass once-a-day limit

    if ($data['action'] === 'time_in') {
        $bypass_message = null;
        if ($disable_once_a_day_limit) {
            $bypass_message = 'Bypass active, proceeding with time in.';
        } else {
            // Check if the user has already logged in for today
            $sql_check = "SELECT time_in FROM tbl_attendance 
                          WHERE pre_service_teacher_id = ? 
                          AND placement_id = ? 
                          AND DATE(time_in) = ? 
                          ORDER BY time_in DESC LIMIT 1";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("iis", $pre_service_teacher_id, $placement_id, $current_date);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // User has already logged in for today, prevent further logins
                echo json_encode(['success' => false, 'message' => 'You have already logged in today.']);
                exit();
            }
        }

        // Define status as 'pending'
        $status = 'pending';
        $approved_by = null;  // Assuming no approver initially

        // Insert time_in with the current server datetime
        $sql = "INSERT INTO tbl_attendance (placement_id, pre_service_teacher_id, time_in, status, approved_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissi", $placement_id, $pre_service_teacher_id, $server_datetime, $status, $approved_by);

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Time in recorded.'];
            if ($bypass_message) {
                $response['bypass_message'] = $bypass_message;
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        exit();

    } elseif ($data['action'] === 'time_out') {
        // Update only the time_out field, ensuring that time_out is NULL before updating
        $sql = "UPDATE tbl_attendance 
                SET time_out = ? 
                WHERE placement_id = ? 
                  AND pre_service_teacher_id = ? 
                  AND time_out IS NULL 
                ORDER BY id DESC 
                LIMIT 1";  // Only update the latest record
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $server_datetime, $placement_id, $pre_service_teacher_id);

        if ($stmt->execute()) {
            // Fetch the time_in and time_out after updating
            $sql_fetch = "SELECT time_in, time_out FROM tbl_attendance 
                          WHERE pre_service_teacher_id = ? 
                          AND placement_id = ? 
                          ORDER BY id DESC LIMIT 1";
            $stmt_fetch = $conn->prepare($sql_fetch);
            $stmt_fetch->bind_param("ii", $pre_service_teacher_id, $placement_id);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            
            if ($result_fetch->num_rows > 0) {
                $attendance = $result_fetch->fetch_assoc();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Time out recorded.',
                    'time_in' => $attendance['time_in'], 
                    'time_out' => $attendance['time_out']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error fetching attendance data.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
    }
}

exit();

?>
