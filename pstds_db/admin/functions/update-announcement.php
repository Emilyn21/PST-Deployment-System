<?php
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $announcementId = $_POST['announcementId'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $audience = $_POST['audience'];
    $type = $_POST['type'];
    $file_url = null;

    $conn->begin_transaction();

    try {
        if (isset($_POST['removeAttachment'])) {
            $updateQuery = "UPDATE tbl_announcement 
                            SET title = ?, content = ?, audience = ?, announcement_type = ?, file_url = NULL, updated_at = NOW()
                            WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssssi", $title, $content, $audience, $type, $announcementId);
        } elseif (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
            $target_dir = "../../uploads/a/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $file_name = basename($_FILES["file"]["name"]);
            $unique_file_name = uniqid() . "_" . $file_name;
            $target_file = $target_dir . $unique_file_name;

            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                $file_url = $unique_file_name;
                $updateQuery = "UPDATE tbl_announcement 
                                SET title = ?, content = ?, audience = ?, announcement_type = ?, file_url = ?
                                WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("sssssi", $title, $content, $audience, $type, $file_url, $announcementId);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to upload the file. Please try again.']);
                exit();
            }
        } else {
            $updateQuery = "UPDATE tbl_announcement 
                            SET title = ?, content = ?, audience = ?, announcement_type = ?
                            WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssssi", $title, $content, $audience, $type, $announcementId);
        }

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Announcement updated successfully.']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to update announcement. Please try again.']);
        }

        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred while updating the announcement.']);
    }
} else {
    header('Location: ../index.php');
    exit();
}

$conn->close();
?>