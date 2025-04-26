<?php
include 'auth.php';

// Fetch the current user data from tbl_user
$sql = "SELECT * FROM tbl_user WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first-name']);
    $first_name = !empty($first_name) ? strtoupper($first_name) : strtoupper($user['first_name']);
    $middle_name = trim($_POST['middle-name']);
    $middle_name = !empty($middle_name) ? strtoupper($middle_name) : null;
    $last_name = trim($_POST['last-name']);
    $last_name = !empty($last_name) ? strtoupper($last_name) : strtoupper($user['last_name']);
    $contact_number = trim($_POST['contact-number']);
    $contact_number = !empty($contact_number) ? $contact_number : null;
    $sex = !empty($_POST['sex']) ? $_POST['sex'] : $user['sex'];
    $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : $user['birthdate'];

    if (isset($_FILES['profile-picture']) && $_FILES['profile-picture']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['profile-picture']['tmp_name']);
    } else {
        $imageData = $user['profile_picture'];
    }

    $sql = "UPDATE tbl_user SET first_name = ?, middle_name = ?, last_name = ?, contact_number = ?, sex = ?, birthdate = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssi', $first_name, $middle_name, $last_name, $contact_number, $sex, $birthdate, $imageData, $user_id);

    try {
        if ($stmt->execute()) {
            $street = trim($_POST['street']);
            $street = !empty($street) ? strtoupper($street) : null;
            $barangay = trim($_POST['barangay']);
            $barangay = !empty($barangay) ? strtoupper($barangay) : null;
            $city_municipality = trim($_POST['city_municipality']);
            $city_municipality = !empty($city_municipality) ? strtoupper($city_municipality) : null;
            $province = trim($_POST['province']);
            $province = !empty($province) ? strtoupper($province) : null;

            $sql_address = "UPDATE tbl_user SET street = ?, barangay = ?, city_municipality = ?, province = ? WHERE id = ?";
            $stmt_address = $conn->prepare($sql_address);
            $stmt_address->bind_param('ssssi', $street, $barangay, $city_municipality, $province, $user_id);

            if ($stmt_address->execute()) {
                $_SESSION['update_success'] = true;
                header('Location: ../account.php');
                exit();
            } else {
                $_SESSION['update_success'] = false;
                echo "Error updating address information: " . $stmt_address->error;
            }
        } else {
            $_SESSION['update_success'] = false;
            echo "Error updating profile: " . $stmt->error;
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['update_success'] = false;
        header('Location: ../account.php');
        exit();
    }
} else {
    header('Location: ../../cooperating-teacher/index.php');
    exit();
}
?>