<?php
include 'auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $audience = $_POST['audience'][0];
    $type = $_POST['type'];
    $created_by = $_SESSION['user_id'];
    $file_url = null;

    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
        $target_dir = "../../uploads/a/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file_name = basename($_FILES["file"]["name"]);
        $unique_file_name = uniqid() . "_" . $file_name;
        $target_file = $target_dir . $unique_file_name;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $file_url = $unique_file_name;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
            exit();
        }
    }

    // Insert the announcement into the database
    $stmt = $conn->prepare("
        INSERT INTO tbl_announcement (title, content, audience, announcement_type, file_url, created_by) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssi", $title, $content, $audience, $type, $file_url, $created_by);

    if ($stmt->execute()) {
        // Role-audience mapping
        $roleMapping = [
            "pre-service teacher" => "pre-service teacher",
            "cooperating_teacher" => "cooperating teacher",
            "school_admin" => "school_admin",
            "adviser" => "adviser",
        ];

        $usersQuery = "";
        $bindParams = [];

        if (strtolower($audience) === "all") {
            // Select all users where isDeleted is 0 and account_status is active
            $usersQuery = "
                SELECT id 
                FROM tbl_user 
                WHERE isDeleted = 0 AND account_status = 'active'
            ";
        } else {
            // Get the corresponding role for the selected audience
            $mappedRole = array_search($audience, $roleMapping);

            if ($mappedRole) {
                $usersQuery = "
                    SELECT id 
                    FROM tbl_user 
                    WHERE role = ? AND isDeleted = 0 AND account_status = 'active'
                ";
                $bindParams = [$mappedRole];
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Audience role mapping failed.'
                ]);
                exit();
            }
        }

        // Prepare the query and bind parameters if needed
        $userQuery = $conn->prepare($usersQuery);

        if (!empty($bindParams)) {
            $userQuery->bind_param("s", ...$bindParams);
        }

        $userQuery->execute();
        $result = $userQuery->get_result();

        // Insert notifications for the matching users
        $notificationStmt = $conn->prepare("
            INSERT INTO tbl_notification (user_id, message, link, type) 
            VALUES (?, ?, 'announcement.php', 'alert')
        ");
        $notificationMessage = "Admin posted an announcement: \"$title\".";

        while ($row = $result->fetch_assoc()) {
            $userId = $row['id'];

            // Skip the author's ID when audience is "all"
            if (strtolower($audience) === "all" && $userId == $created_by) {
                continue;
            }

            $notificationStmt->bind_param("is", $userId, $notificationMessage);
            $notificationStmt->execute();
        }

        $notificationStmt->close();
        $userQuery->close();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Announcement added successfully, and notifications sent!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    header('Location: ../index.php');
    exit();
}
?>
