<?php
include 'includes/auth.php';

// Get logged-in user details and check if user is a pre-service teacher
$user_id = $_SESSION['user_id'];
$sqlCheckTeacher = "SELECT id FROM tbl_pre_service_teacher WHERE user_id = ?";
$stmt = $conn->prepare($sqlCheckTeacher);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Redirect if user is not a pre-service teacher
if ($result->num_rows == 0) {
    header('Location: ../login.php');
    exit();
}

$pre_service_teacher_id = $result->fetch_assoc()['id'];

// Query to fetch profile picture
$sqlProfilePicture = "SELECT profile_picture FROM tbl_user WHERE id = ?";
$stmt = $conn->prepare($sqlProfilePicture);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultProfilePicture = $stmt->get_result();
$profile_picture = null;

if ($resultProfilePicture->num_rows > 0) {
    $profile_picture = $resultProfilePicture->fetch_assoc()['profile_picture'];
}

// Query to fetch evaluation data for the logged-in pre-service teacher
$sql = "SELECT 
            tev.id AS evaluation_id,
            tev.eportfolio_grade,
            tev.internship_grade,
            tev.final_demo_average,
            tev.overall_average,
            tu.first_name,
            tu.middle_name,
            tu.last_name,
            tep.file_link AS eportfolio_attachment,
            tog.observer_number,
            tog.grade AS observer_grade,
            tog.attachment_link AS observer_attachment,
            tpl.id AS placement_id
        FROM 
            tbl_evaluation tev
        JOIN 
            tbl_placement tpl ON tev.placement_id = tpl.id
        JOIN
            tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
        JOIN 
            tbl_user tu ON tpst.user_id = tu.id 
        LEFT JOIN 
            tbl_eportfolio tep ON tep.pre_service_teacher_id = tpl.pre_service_teacher_id
        LEFT JOIN 
            tbl_observer_grades tog ON tog.evaluation_id = tev.id
        WHERE 
            tpst.id = ?
            AND tog.isActive = 0
            AND tu.isDeleted = 0
            AND tpl.isDeleted = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pre_service_teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Fetch the active evaluation criteria percentages
$sqlCriteria = "SELECT 
                    eportfolio_percentage, 
                    internship_percentage, 
                    final_demo_percentage 
                FROM tbl_evaluation_criteria_percentage 
                WHERE isActive = 1 AND isDeleted = 0 
                LIMIT 1";

$resultCriteria = $conn->query($sqlCriteria);
if ($resultCriteria->num_rows > 0) {
    $criteria = $resultCriteria->fetch_assoc();
    $eportfolio_percentage = $criteria['eportfolio_percentage'];
    $internship_percentage = $criteria['internship_percentage'];
    $final_demo_percentage = $criteria['final_demo_percentage'];
} else {
    echo "No active evaluation criteria found.";
    exit();
}

$evaluations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluation_id = $row['evaluation_id'];

        if (!array_key_exists($evaluation_id, $evaluations)) {
            $evaluations[$evaluation_id] = [
                'user_id' => $user_id ?? 'N/A',
                'pre_service_teacher_id' => $pre_service_teacher_id ?? 'N/A',
                'placement_id' => $row['placement_id'] ?? 'N/A',
                'first_name' => $row['first_name'] ?? 'N/A',
                'middle_name' => $row['middle_name'] ?? '',
                'last_name' => $row['last_name'] ?? 'N/A',
                'eportfolio_grade' => $row['eportfolio_grade'] ?? 0,
                'internship_grade' => $row['internship_grade'] ?? 0,
                'final_demo_average' => $row['final_demo_average'] ?? 0,
                'overall_average' => $row['overall_average'] ?? 0,
                'eportfolio_attachment' => $row['eportfolio_attachment'] ?? '',
                'eportfolio_percentage' => $eportfolio_percentage ?? 0,
                'internship_percentage' => $internship_percentage ?? 0,
                'final_demo_percentage' => $final_demo_percentage ?? 0,
                'observer_grades' => [],
                'profile_picture' => $profile_picture // Add profile picture to evaluation data
            ];
        }

        // Only add observer grades if observer_number exists
        if ($row['observer_number'] !== null) {
            $evaluations[$evaluation_id]['observer_grades'][] = [
                'observer_number' => $row['observer_number'],
                'observer_grade' => $row['observer_grade'],
                'observer_attachment' => $row['observer_attachment']
            ];
        }
    }
} else {
    echo "No evaluation data found.";
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Evaluation</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        header nav a { color: #9baec8; }
        header nav a:hover { color: white; }
        .card { margin-top: 2rem; }
        .remarks { padding: 10px; background-color: #f8f9fa; border-left: 5px solid #007bff; margin-top: 1rem; }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-5 h3">Evaluation</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Evaluation</li>
                    </ol>
<section class="row">
    <?php foreach ($evaluations as $evaluation): ?>
    <article class="col-md-12">
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <img src="<?= !empty($profile_picture) ? 'data:image/jpeg;base64,' . base64_encode($profile_picture) : '../assets/img/default-image.jpg'; ?>" 
                             class="rounded-circle" width="80" height="80" alt="Profile Picture">
                    </div>
                    <div>
                        <h5 class="mb-1"><?= "{$evaluation['last_name']}, {$evaluation['first_name']} {$evaluation['middle_name']}" ?></h5>
                        <p class="text-muted small mb-0">Bachelor of Secondary Education, English</p>
                    </div>
                </div>

                <h6 class="fw-bold">Internship Performance: <span class="text-primary"><?= "{$evaluation['internship_grade']}%" ?></span></h6>
                <h6 class="fw-bold">e-Portfolio: <span class="text-primary"><?= "{$evaluation['eportfolio_grade']}%" ?></span></h6>

                <h6 class="mt-3">Final Demo</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Observer</th>
                            <th>Grade</th>
                            <th>Attachment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluation['observer_grades'] as $observer): ?>
                        <tr>
                            <td>Observer <?= $observer['observer_number'] ?></td>
                            <td><strong><?= "{$observer['observer_grade']}%" ?></strong></td>
                            <td>
                                <?= $observer['observer_attachment'] ? "<a href='../uploads/og/{$observer['observer_attachment']}' target='_blank'>View</a>" : "No Attachment" ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h6 class="mt-3">Final Grades</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Component</th>
                            <th>Grade</th>
                            <th>Weight</th>
                            <th>Weighted Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Internship</td>
                            <td><?= "{$evaluation['internship_grade']}%" ?></td>
                            <td><?= "{$evaluation['internship_percentage']}%" ?></td>
                            <td><?= number_format(($evaluation['internship_grade'] * $evaluation['internship_percentage']) / 100, 2) ?>%</td>
                        </tr>
                        <tr>
                            <td>e-Portfolio</td>
                            <td><?= "{$evaluation['eportfolio_grade']}%" ?></td>
                            <td><?= "{$evaluation['eportfolio_percentage']}%" ?></td>
                            <td><?= number_format(($evaluation['eportfolio_grade'] * $evaluation['eportfolio_percentage']) / 100, 2) ?>%</td>
                        </tr>
                        <tr>
                            <td>Final Demo</td>
                            <td><?= "{$evaluation['final_demo_average']}%" ?></td>
                            <td><?= "{$evaluation['final_demo_percentage']}%" ?></td>
                            <td><?= number_format(($evaluation['final_demo_average'] * $evaluation['final_demo_percentage']) / 100, 2) ?>%</td>
                        </tr>
                        <tr class="table-success">
                            <td colspan="3"><strong>Final Grade</strong></td>
                            <td><strong><?= number_format($evaluation['overall_average'], 2) ?>%</strong></td>
                        </tr>
                    </tbody>
                </table>

                <h6 class="mt-3">Overall Remarks</h6>
                <p class="text-muted"><?= "{$evaluation['first_name']} has consistently performed at an excellent level across all evaluated components." ?></p>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</section>


                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
