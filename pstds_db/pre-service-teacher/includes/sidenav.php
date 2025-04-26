<?php
include '../connect.php';

function getUserDataAndPlacement($user_id) {
    global $conn;

    // Fetch user fullname
    $sql = "
        SELECT 
            CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(last_name, '')) AS fullname
        FROM tbl_user
        WHERE id = ?
    ";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ['error' => 'Error in preparing user statement: ' . $conn->error];
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $user_data = $result->num_rows > 0 ? $result->fetch_assoc() : [];
    $user_name = $user_data['fullname'] ?? 'Unknown User';

    // Check `placement_id` in `tbl_placement`
    $sql_placement = "
        SELECT p.id AS placement_id
        FROM tbl_placement p
        JOIN tbl_pre_service_teacher pst ON pst.id = p.pre_service_teacher_id
        JOIN tbl_user u ON u.id = pst.user_id
        WHERE u.id = ? AND p.status = 'approved'
    ";

    $stmt_placement = $conn->prepare($sql_placement);

    if (!$stmt_placement) {
        return ['error' => 'Error in preparing placement statement: ' . $conn->error];
    }

    $stmt_placement->bind_param("i", $user_id);
    $stmt_placement->execute();
    $result_placement = $stmt_placement->get_result();

    $placement_id = $result_placement->num_rows > 0 ? $result_placement->fetch_assoc()['placement_id'] : null;

    // Check if `placement_id` exists in `tbl_evaluation`
    $evaluation_exists = false;
    if ($placement_id) {
        $sql_evaluation = "
            SELECT id 
            FROM tbl_evaluation
            WHERE placement_id = ?
        ";
        $stmt_evaluation = $conn->prepare($sql_evaluation);

        if (!$stmt_evaluation) {
            return ['error' => 'Error in preparing evaluation statement: ' . $conn->error];
        }

        $stmt_evaluation->bind_param("i", $placement_id);
        $stmt_evaluation->execute();
        $result_evaluation = $stmt_evaluation->get_result();

        $evaluation_exists = $result_evaluation->num_rows > 0;
    }

    return [
        'user_name' => $user_name,
        'placement_id' => $placement_id,
        'evaluation_exists' => $evaluation_exists,
    ];
}


if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $data = getUserDataAndPlacement($user_id);

    if (isset($data['error'])) {
        echo $data['error'];
        exit();
    }

    $user_name = $data['user_name'];
    $placement_id = $data['placement_id'];
    $evaluation_exists = $data['evaluation_exists']; // Add this line
} else {
    header('Location: ../login.php');
    exit();
}

?>


<div id="layoutSidenav_nav" class="mt-3">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <!-- Core Section -->
                <a class="nav-link" href="index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                <a class="nav-link" href="account.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-circle"></i></div>
                    Account
                </a>
                <?php if ($placement_id): ?>
                    <a class="nav-link" href="attendance.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                        Attendance
                    </a>
                <?php endif; ?>
                <a class="nav-link" href="announcement.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-bullhorn"></i></div>
                    Announcements
                </a>
                <?php if ($placement_id): ?>
                    <a class="nav-link" href="eportfolio.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
                        e-Portfolio
                    </a>
                    <?php if ($evaluation_exists): ?>
                        <a class="nav-link" href="evaluation.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                            Evaluation
                        </a>
                    <?php endif; ?>

                <!-- Journal -->
                <a class="nav-link" href="manage-journal-entry.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                    Daily Journal
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="sb-sidenav-footer">
        </div>
    </nav>
</div>
<style>
    .sb-sidenav-menu-heading {
        font-family: 'Segoe UI';
    }
</style>
