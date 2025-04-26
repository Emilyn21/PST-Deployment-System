<?php 
header("Content-Security-Policy: default-src 'self'; " . 
    "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; " . 
    "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; " . 
    "img-src 'self' data:; " . 
    "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " . 
    "connect-src 'self';");

include 'includes/auth.php';

$academicYearName = '';
$semesterType = '';
$firstNamePrep = '';
$lastNamePrep = '';

$semesterStmt = $conn->prepare("
    SELECT id, type
    FROM tbl_semester
    WHERE status = 'active' 
      AND isDeleted = 0 
    ORDER BY start_date DESC 
    LIMIT 1
");
$semesterStmt->execute();
$semesterResult = $semesterStmt->get_result();

if ($semesterRow = $semesterResult->fetch_assoc()) {
    $activeSemesterId = $semesterRow['id'];
    $semesterType = $semesterRow['type']; // Get semester type directly

    // Fetch academic year for the active semester
    $stmtAcademicYear = $conn->prepare("
        SELECT tay.academic_year_name
        FROM tbl_academic_year tay
        INNER JOIN tbl_semester ts ON tay.id = ts.academic_year_id
        WHERE ts.id = ?
    ");
    $stmtAcademicYear->bind_param('i', $activeSemesterId);
    $stmtAcademicYear->execute();
    $academicYearResult = $stmtAcademicYear->get_result();

    if ($academicYearRow = $academicYearResult->fetch_assoc()) {
        $academicYearName = $academicYearRow['academic_year_name'];
    }

    // Main query to fetch pre-service teacher details
    $stmt = $conn->prepare("SELECT DISTINCT
                tpst.id,
                tu.email, 
                tu.profile_picture,
                tu.first_name, 
                tu.middle_name, 
                tu.last_name,
                tpst.student_number, 
                CONCAT(tu.last_name, ', ', tu.first_name, 
                CASE 
                    WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
                    ELSE ''
                END) AS student_name,
                TRIM(
                    CONCAT(
                        tu.first_name, 
                        CASE 
                            WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name) 
                            ELSE '' 
                        END, 
                        ' ', 
                        tu.last_name
                    )
                ) AS fml_name,
                CONCAT(
                COALESCE(tp.program_abbreviation, 'N/A'),
                    CASE 
                        WHEN tm.major_abbreviation IS NOT NULL THEN CONCAT('-', tm.major_abbreviation)
                        ELSE ''
                    END
                ) AS program_major, 
                tpst.program_id, 
                tm.major_name AS major, 
                tpst.major_id, 
                CASE 
                    WHEN COALESCE(tu.street, tu.barangay, tu.city_municipality, tu.province) IS NULL 
                    THEN ''
                    ELSE TRIM(
                        CONCAT(
                            COALESCE(tu.street, ''), 
                            CASE WHEN tu.street IS NOT NULL AND tu.barangay IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(tu.barangay, ''), 
                            CASE WHEN tu.barangay IS NOT NULL AND tu.city_municipality IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(tu.city_municipality, ''), 
                            CASE WHEN tu.city_municipality IS NOT NULL AND tu.province IS NOT NULL THEN ', ' ELSE ' ' END,
                            COALESCE(tu.province, '')
                        )
                    )
                END AS address,
                tu.contact_number,
                tay.academic_year_name, 
                ts.academic_year_id, 
                tpst.semester_id,
                CONCAT(
                    COALESCE(tay.academic_year_name, 'N/A'),
                    CASE 
                        WHEN ts.type IS NOT NULL THEN CONCAT(' - ', ts.type)
                        ELSE ''
                    END
                ) AS acad_semester,
                tu.account_status,
                tpst.placement_status
            FROM 
                tbl_pre_service_teacher tpst
            INNER JOIN 
                tbl_user tu ON tpst.user_id = tu.id
            INNER JOIN 
                tbl_semester ts ON tpst.semester_id = ts.id
            INNER JOIN
                tbl_academic_year tay ON ts.academic_year_id = tay.id
            INNER JOIN 
                tbl_program tp ON tpst.program_id = tp.id
            LEFT JOIN 
                tbl_major tm ON tpst.major_id = tm.id
            WHERE 
                tu.role = 'pre-service teacher' 
                AND tu.isDeleted = 0
                AND tay.status = 'active'
                AND tpst.semester_id = ?
            ORDER BY 
                tay.start_date DESC,  
                (CASE tu.account_status 
                    WHEN 'active' THEN 1 
                    WHEN 'inactive' THEN 2 
                    ELSE 3 
                END),
                tu.last_name ASC");
    $stmt->bind_param('i', $activeSemesterId); // Bind the semester ID
    $stmt->execute();
    $result = $stmt->get_result();

    $stmt2 = $conn->prepare("SELECT tu.first_name, tu.last_name FROM tbl_user tu WHERE tu.id = ?");
    $stmt2->bind_param('i', $user_id); // Bind the semester ID
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($nameRow = $result2->fetch_assoc()) {
        $firstNamePrep = $nameRow['first_name'];
        $lastNamePrep = $nameRow['last_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Manage Pre-Service Teacher - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        } @media (max-width: 576px) {
            .flex-wrap-nowrap {
                flex-wrap: wrap !important;
            }
        } .btn-action {
            margin-top: 0.2rem;
        } .form-group {
            margin-bottom: 0.15rem;
        }
        th:nth-child(6), td:nth-child(6) {
            white-space: nowrap;
            width: auto !important;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav" role="navigation">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main role="main">
                <div class="container-fluid px-4">
                    <h1 class="mt-5 h3" id="main-heading">Manage Pre-Service Teachers</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Pre-Service Teachers</li>
                    </ol>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as total 
                        FROM tbl_pre_service_teacher pst
                        JOIN tbl_user u ON pst.user_id = u.id
                        WHERE u.isDeleted = 0 AND pst.semester_id = ?
                    ");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $totalTeachersResult = $stmt->get_result();
                    $totalTeachers = $totalTeachersResult->fetch_assoc()['total'];
                    $stmt->close();

                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as total 
                        FROM tbl_pre_service_teacher pst
                        JOIN tbl_user u ON pst.user_id = u.id
                        WHERE pst.placement_status = 'placed' AND u.isDeleted = 0 AND u.account_status = 'active' AND pst.semester_id = ?
                    ");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $placedTeachersResult = $stmt->get_result();
                    $placedTeachers = $placedTeachersResult->fetch_assoc()['total'];
                    $stmt->close();

                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as total 
                        FROM tbl_pre_service_teacher pst
                        JOIN tbl_user u ON pst.user_id = u.id
                        WHERE pst.placement_status = 'unplaced' AND u.isDeleted = 0 AND u.account_status = 'active' AND pst.semester_id = ?
                    ");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $unplacedTeachersResult = $stmt->get_result();
                    $unplacedTeachers = $unplacedTeachersResult->fetch_assoc()['total'];
                    $stmt->close();

                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as total 
                        FROM tbl_pre_service_teacher pst
                        JOIN tbl_user u ON pst.user_id = u.id
                        WHERE u.isDeleted = 0 AND u.account_status = 'inactive' AND pst.semester_id = ?
                    ");
                    $stmt->bind_param('i', $activeSemesterId);
                    $stmt->execute();
                    $inactiveTeachersResult = $stmt->get_result();
                    $inactiveTeachers = $inactiveTeachersResult->fetch_assoc()['total'];
                    $stmt->close();
                    ?>

                    <!-- Dashboard Summary Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-3" role="region" aria-label="Total Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-users me-2"></i>
                                        <span>Total Pre-Service Teachers</span>
                                    </div>
                                    <h3 class="mb-0"><?= $totalTeachers ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-3" role="region" aria-label="Placed Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-check me-2"></i>
                                        <span>Placed Pre-Service Teachers</span>
                                    </div>
                                    <h3 class="mb-0"><?= $placedTeachers ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-3" role="region" aria-label="Unplaced Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-times me-2"></i>
                                        <span>Unplaced Pre-Service Teachers</span>
                                    </div>
                                    <h3 class="mb-0"><?= $unplacedTeachers ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-danger text-white mb-3" role="region" aria-label="Inactive Pre-Service Teachers">
                                <div class="card-header d-flex align-items-center justify-content-between py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-slash me-2"></i>
                                        <span>Inactive Pre-Service Teachers</span>
                                    </div>
                                    <h3 class="mb-0"><?= $inactiveTeachers ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                View, edit, and manage pre-service teachers' information, including student number, name, program, and status. Use the "Edit" button to update details and "Delete" to remove entries. You can also filter and search for specific teachers.
                            </p>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Pre-Service Teachers
                        </div>
                        <div class="card-body">
                            <div id="semesterInfo" style="display: none;"><?php echo htmlspecialchars($semesterType); ?></div>
                            <div id="academicYearInfo" style="display: none;"><?php echo htmlspecialchars($academicYearName); ?></div>
                            <div id="firstNamePrep" style="display: none;"><?php echo htmlspecialchars($firstNamePrep); ?></div>
                            <div id="lastNamePrep" style="display: none;"><?php echo htmlspecialchars($lastNamePrep); ?></div>
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV()" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel()" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Pre-Service Teachers Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Student Number</th>
                                        <th scope="col" role="columnheader">Name</th>
                                        <th scope="col" role="columnheader">Email Address</th>
                                        <th scope="col" role="columnheader">Program-Major</th>
                                        <th scope="col" role="columnheader">Status</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()) : ?>
                                        <?php
                                        if (!empty($row['profile_picture'])) {
                                            $profilePictureBase64 = base64_encode($row['profile_picture']);
                                        } else {
                                            $profilePictureBase64 = null;
                                        }
                                        ?>
                                    <tr role="row">
                                        <td class="text-center" role="cell"><?= htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td role="cell">
                                            <?php
                                            $status = ucfirst($row['account_status']);
                                            $badgeClass = ($row['account_status'] === 'active') ? 'badge bg-success' : 'badge bg-danger';
                                            ?>
                                            <span class="<?= $badgeClass; ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td role="cell">
                                            <button class="btn btn-sm btn-info btn-action" 
                                                onclick="openViewModal(
                                                    <?= $row['id']; ?>,
                                                    <?= htmlspecialchars(json_encode($row['student_number']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['student_name']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['email']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($profilePictureBase64), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['address']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['contact_number']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode(strtoupper($row['acad_semester'])), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['program_major']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['placement_status']), ENT_QUOTES, 'UTF-8'); ?>,
                                                    <?= htmlspecialchars(json_encode($row['account_status']), ENT_QUOTES, 'UTF-8'); ?>
                                                    )">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning btn-action" 
                                                onclick="openEditModal(
                                                    <?= $row['id']; ?>,
                                                    <?= $row['student_number']; ?>,
                                                    <?= $row['student_number']; ?>, 
                                                    <?= htmlspecialchars(json_encode($row['first_name']), ENT_QUOTES, 'UTF-8'); ?>,  
                                                    <?= htmlspecialchars(json_encode($row['middle_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['last_name']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= htmlspecialchars(json_encode($row['email']), ENT_QUOTES, 'UTF-8'); ?>, 
                                                    <?= $row['program_id']; ?>,
                                                    <?= $row['major_id'] !== null ? $row['major_id'] : 'null'; ?>,
                                                    <?= $row['semester_id']; ?>, 
                                                    <?= htmlspecialchars(json_encode($row['account_status']), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action" 
                                                onclick="openDeleteModal(
                                                    <?= $row['id']; ?>,
                                                    <?= htmlspecialchars(json_encode($row['fml_name']), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>      
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="viewModal" role="dialog" tabindex="-1" aria-labelledby="viewModalLabel" aria-modal="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header bg-info">
            <h5 class="modal-title" id="viewModalLabel">
              <i class="fa fa-eye"></i> View Pre-Service Teacher
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Profile Section -->
            <div class="d-flex align-items-center mb-3">
              <img id="viewProfilePicture" alt="Profile Picture" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
              <div class="ms-3">
                <h5 id="viewStudentName" class="mb-0"></h5>
                <p id="viewStudentNumber" class="text-muted mb-0"></p>
                
                <!-- Account and Placement Status as badges -->
                <p id="viewStatus" class="mb-0"> 
                  <span id="viewAccountStatus" class="badge"></span>
                  <span class="text-muted"> | </span>
                  <span id="viewPlacementStatus" class="badge"></span>
                </p>
              </div>
            </div>

            <!-- Personal Information Card -->
            <h6 class="fw-bold"><i class="fa fa-user"></i> Personal Information</h6>
            <ul class="list-group mb-3">
              <li class="list-group-item"><i class="fa fa-envelope"></i> <strong>Email:</strong> <span id="viewEmail"></span></li>
              <li class="list-group-item"><i class="fa fa-map-marker-alt"></i> <strong>Address:</strong> <span id="viewAddress"></span></li>
              <li class="list-group-item"><i class="fa fa-phone"></i> <strong>Contact Number:</strong> <span id="viewContactNumber"></span></li>
            </ul>

            <!-- Academic Information Card -->
            <h6 class="fw-bold"><i class="fa fa-graduation-cap"></i> Academic Information</h6>
            <ul class="list-group mb-3">
              <li class="list-group-item"><i class="fa fa-calendar-alt"></i> <strong>Academic Year & Semester:</strong> <span id="viewAcademicYearSemester"></span></li>
              <li class="list-group-item"><i class="fa fa-book-open"></i> <strong>Program & Major:</strong> <span id="viewProgramMajor"></span></li>
            </ul>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" role="dialog" tabindex="-1" aria-labelledby="editModalLabel" aria-modal="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel"><i class="fa fa-edit"></i> Edit Pre-Service Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST"method="POST" action="functions/update-pre-service-teacher.php" aria-labelledby="editModalLabel">
                        <input type="hidden" id="editPreServiceTeacherId" name="editPreServiceTeacherId">
                        <div class="mb-3">
                            <label for="editStudentNumber" class="form-label">Student Number</label>
                            <input type="number" class="form-control" id="editStudentNumber" name="studentNumber" required aria-required="true" role="textbox">
                            <div class="invalid-feedback">
                                Student number should be exactly 9 digits.
                            </div>
                            <input type="hidden" id="oldStudentNumber" name="oldStudentNumber">
                        </div>
                        <div class="mb-3">
                            <label for="editFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editFirstName" name="firstName" required aria-required="true" role="textbox">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editMiddleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="editMiddleName" name="middleName" role="textbox">
                            </div>
                            <div class="col-md-6">
                                <label for="editLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editLastName" name="lastName" required aria-required="true" role="textbox">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="editEmail" name="email" readonly disabled role="textbox">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editProgram" class="form-label">Program</label>
                                <select class="form-select" id="editProgram" name="program" onchange="updateMajors(this.value, '');" required aria-required="true" role="combobox">
                                    <?php
                                    // Fetch the programId from the session, request, or database
                                    $programId = isset($_GET['programId']) ? $_GET['programId'] : null;

                                    $programsQuery = "SELECT * FROM tbl_program WHERE status = ? AND isDeleted = ?";
                                    $stmt = $conn->prepare($programsQuery);
                                    $status = 'active';
                                    $isDeleted = 0;
                                    $stmt->bind_param("si", $status, $isDeleted);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    echo "<option value='null' disabled selected>Select program</option>";
                                    while ($program = $result->fetch_assoc()) {
                                        $selected = ($program['id'] == $programId) ? 'selected' : '';
                                        echo "<option value='{$program['id']}' $selected>{$program['program_abbreviation']}</option>";
                                    }
                                    $stmt->close();
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editMajor" class="form-label">Major</label>
                                <select class="form-select" id="editMajor" name="major" required aria-required="true" role="combobox">
                                    <!-- The major options will be populated by JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editSemester" class="form-label">Semester</label>
                                <select class="form-select" id="editSemester" name="semester" required aria-required="true" role="combobox">
                                    <?php
                                    $academicYearsQuery = "SELECT s.id AS semester_id, s.type, ay.academic_year_name FROM tbl_semester s JOIN tbl_academic_year ay ON ay.id = s.academic_year_id WHERE s.status = 'active' AND s.isDeleted = 0";
                                    $stmt = $conn->prepare($academicYearsQuery);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    while ($year = $result->fetch_assoc()) {
                                        // Use ucfirst() to capitalize the first letter of the 'type' value
                                        $type = ucfirst($year['type']);
                                        echo "<option value='{$year['semester_id']}'>{$year['academic_year_name']}: {$type}</option>";
                                    }
                                    $stmt->close();
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="account_status" required aria-required="true" role="combobox">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editForm" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Success Modal -->
    <div class="modal fade" id="editSuccessModal" role="dialog" tabindex="-1" aria-labelledby="editSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editSuccessModalLabel"><i class="fas fa-check-circle"></i> Edit Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The pre-service teacher details have been successfully updated.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Error Modal -->
    <div class="modal fade" id="editErrorModal" tabindex="-1" role="dialog" aria-labelledby="editErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="editErrorModalLabel"><i class="fas fa-exclamation-circle"></i> Edit Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editErrorMessage">
                    <!-- Error message will be dynamically inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" role="dialog" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-trash"></i> Delete Pre-Service Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Error Modal -->
    <div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-labelledby="deleteErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteErrorModalLabel"><i class="fas fa-exclamation-circle"></i> Delete Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Success Modal -->
    <div class="modal fade" id="deleteSuccessModal" role="dialog" tabindex="-1" aria-labelledby="deleteSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteSuccessModalLabel"><i class="fa fa-trash"></i> Delete Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The pre-service teacher has been successfully deleted.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast position-fixed top-50 start-50 translate-middle" style="display: none; z-index: 1050;">
        <div class="toast-body bg-success text-white">
            Table copied to clipboard
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script src="../js/xlsx.full.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                const dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No pre-service teachers have been added for the current active academic year and semester."
                    },
                    perPage: 10, // Default entries per page
                    perPageSelect: [10, 25, 50, 100, -1] // Includes 50 and "All"
                });
                // Modify "-1" to show as "All" in dropdown
                setTimeout(() => {
                    document.querySelectorAll(".datatable-dropdown option").forEach(option => {
                        if (option.value == "-1") {
                            option.textContent = "All"; // Change "-1" to "All"
                        }
                    });
                }, 100);
            }

            const majorsData = <?php
                $majorsQuery = "SELECT * FROM tbl_major WHERE status = 'active' AND isDeleted = 0";
                $majorsResult = mysqli_query($conn, $majorsQuery);
                $majorsArray = [];
                while ($major = mysqli_fetch_assoc($majorsResult)) {
                    $majorsArray[] = $major;
                }
                echo json_encode($majorsArray);
            ?>;

            window.updateMajors = function(programId, majorId) {
                const majorSelect = document.getElementById('editMajor');
                majorSelect.innerHTML = '';
                majorSelect.innerHTML += '<option value="null" disabled selected>Select major</option>';

                let foundMajor = false; 
                let hasMajor = false;

                majorsData.forEach(major => {
                    if (major.program_id == programId) {
                        foundMajor = true;
                        hasMajor = true;
                        const selected = major.id == majorId ? 'selected' : '';
                        majorSelect.innerHTML += `<option value="${major.id}" ${selected}>${major.major_name}</option>`;
                    }
                });

                if (!foundMajor) {
                    majorSelect.innerHTML = `<option value="null" selected>Not available</option>`;
                }

                if (hasMajor) {
                    majorSelect.setAttribute('required', 'required');
                } else {
                    majorSelect.removeAttribute('required');
                }
            };

            window.openViewModal = function(
                id, studentNumber, studentName, email, profilePicture, address, contactNumber, academicYearSemester, programMajor, placementStatus, accountStatus
            ) {
                // Check if profilePicture is valid (not null or empty)
                const profileImageSrc = profilePicture ? `data:image/jpeg;base64,${profilePicture}` : "../assets/img/default-image.jpg";

                // Set the values inside the modal
                document.getElementById('viewProfilePicture').src = profileImageSrc;
                document.getElementById('viewStudentName').textContent = studentName;
                document.getElementById('viewStudentNumber').textContent = `Student Number: ${studentNumber}`;
                document.getElementById('viewEmail').textContent = email;
                document.getElementById('viewAddress').textContent = address;
                document.getElementById('viewContactNumber').textContent = contactNumber;
                document.getElementById('viewAcademicYearSemester').textContent = academicYearSemester;
                document.getElementById('viewProgramMajor').textContent = programMajor;

                // Set the Account Status badge
                const accountBadge = document.getElementById('viewAccountStatus');
                const accountStatusClass = accountStatus === 'active' ? 'badge bg-success' :
                    accountStatus === 'inactive' ? 'badge bg-danger' : 'badge bg-secondary';
                const accountStatusText = accountStatus === 'active' ? 'Active' :
                    accountStatus === 'inactive' ? 'Inactive' : 'Unknown';

                accountBadge.className = accountStatusClass;
                accountBadge.textContent = accountStatusText;

                // Set the Placement Status badge
                const placementBadge = document.getElementById('viewPlacementStatus');
                const placementStatusClass = placementStatus === 'placed' ? 'badge bg-success' :
                    placementStatus === 'unplaced' ? 'badge bg-danger' :
                    placementStatus === 'pending' ? 'badge bg-warning' : 'badge bg-secondary';
                const placementStatusText = placementStatus === 'placed' ? 'Placed' :
                    placementStatus === 'unplaced' ? 'Unplaced' :
                    placementStatus === 'pending' ? 'Pending' : 'Unknown';

                placementBadge.className = placementStatusClass;
                placementBadge.textContent = placementStatusText;

                // Show the modal
                const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                viewModal.show();
            };

            window.openEditModal = function(preServiceTeacherId, oldStudentNumber, studentNumber, firstName, middleName, lastName, email, programId, majorId, semesterId, status) {
                // Set the values inside the modal
                document.getElementById('editPreServiceTeacherId').value = preServiceTeacherId;
                document.getElementById('editStudentNumber').value = studentNumber;
                document.getElementById('oldStudentNumber').value = oldStudentNumber;
                document.getElementById('editFirstName').value = firstName;
                document.getElementById('editMiddleName').value = middleName;
                document.getElementById('editLastName').value = lastName;
                document.getElementById('editEmail').value = email;
                document.getElementById('editProgram').value = programId;
                document.getElementById('editSemester').value = semesterId;
                document.getElementById('editStatus').value = status;

                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();

                updateMajors(programId, majorId);

                const editStudentNumber = document.getElementById('editStudentNumber');
                const editForm = document.getElementById('editForm');

                // Reuse the real-time validation function
                function validateStudentNumber(input) {
                    const value = input.value;
                    const isValid = /^\d{9}$/.test(value);

                    if (isValid) {
                        input.classList.remove('is-invalid');
                    } else {
                        input.classList.add('is-invalid');
                    }

                    return isValid;
                }

                // Attach real-time validation
                editStudentNumber.addEventListener('input', () => {
                    validateStudentNumber(editStudentNumber);
                });

                // Reset validation upon modal close
                editModal._element.addEventListener('hidden.bs.modal', function() {
                    editStudentNumber.classList.remove('is-invalid', 'is-valid'); // Remove validation classes
                });

                // Validation on form submission
                editForm.onsubmit = function(event) {
                    const isValid = validateStudentNumber(editStudentNumber);
                    if (!isValid) {
                        event.preventDefault(); // Prevent submission if validation fails
                        return; // Exit if validation fails
                    }

                    // Proceed with form submission via fetch
                    event.preventDefault();

                    const formData = Object.fromEntries(new FormData(this).entries());
                    formData.preServiceTeacherId = preServiceTeacherId;

                    fetch('functions/update-pre-service-teacher.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            editModal.hide();
                            const successModal = new bootstrap.Modal(document.getElementById('editSuccessModal'));
                            successModal.show();
                            successModal._element.addEventListener('hidden.bs.modal', function() {
                                window.location.reload();
                            });
                        } else {
                            editModal.hide();
                            document.getElementById('editErrorMessage').textContent = data.message;
                            const errorModal = new bootstrap.Modal(document.getElementById('editErrorModal'));
                            errorModal.show();
                        }
                    })
                    .catch(error => {
                        editModal.hide();
                        console.error('Error:', error);
                        document.getElementById('editErrorMessage').textContent = 'An unexpected error occurred. Please try again later.';
                        const errorModal = new bootstrap.Modal(document.getElementById('editErrorModal'));
                        errorModal.show();
                    });
                };
            };

            window.openDeleteModal = function(preServiceTeacherId, studentName) {
                document.getElementById('deleteName').textContent = studentName;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();

                document.getElementById('confirmDeleteButton').onclick = function() {
                    fetch('functions/delete-pre-service-teacher.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ preServiceTeacherId: preServiceTeacherId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            deleteModal.hide();
                            const deleteSuccessModal = new bootstrap.Modal(document.getElementById('deleteSuccessModal'));
                            document.querySelector('#deleteSuccessModal .modal-body').textContent = data.message;
                            deleteSuccessModal.show();
                            deleteSuccessModal._element.addEventListener('hidden.bs.modal', function () {
                                window.location.reload();
                            });
                        } else {
                            deleteModal.hide();
                            const deleteErrorModal = new bootstrap.Modal(document.getElementById('deleteErrorModal'));
                            document.querySelector('#deleteErrorModal .modal-body').textContent = data.message;
                            deleteErrorModal.show();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        deleteModal.hide();
                        const deleteErrorModal = new bootstrap.Modal(document.getElementById('deleteErrorModal'));
                        document.querySelector('#deleteErrorModal .modal-body').textContent = 'An unexpected error occurred. Please try again later.';
                        deleteErrorModal.show();
                    });
                };
            };
        });

        function copyTable() {
            const table = document.getElementById('datatablesSimple');

            if (!table) return;

            const headers = Array.from(table.querySelectorAll('thead th:not(:last-child)'))
                .map(th => th.innerText.trim())
                .join('\t'); // Join headers with tabs

            const rows = table.querySelectorAll('tbody tr');
            const copiedRows = [];

            rows.forEach(row => {
                const cells = row.querySelectorAll('td:not(:last-child)'); // Exclude action column
                const rowText = Array.from(cells).map(cell => cell.innerText.trim()).join('\t');
                copiedRows.push(rowText);
            });

            const tableText = headers + '\n' + copiedRows.join('\n'); // Add headers at the top

            // Use Clipboard API to copy text
            navigator.clipboard.writeText(tableText).then(() => {
                // Show the toast notification
                const toast = document.getElementById('toast');
                toast.style.display = 'block';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 2000);  // Hide after 2 seconds
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        function exportTableToCSV() {
            // Get the semester and academic year from hidden divs
            const semesterType = document.getElementById('semesterInfo').innerText.trim().toUpperCase();
            const academicYearName = document.getElementById('academicYearInfo').innerText.trim();

            // Construct the filename dynamically, check for null or empty semesterType and academicYearName
            let filename = 'List of Pre-Service Teachers';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }

            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Student Number", "Pre-Service Teacher Name", "Email Address", "Program-Major", "Status"];
            csv.push(headers.map(header => `"${header}"`).join(',')); 

            // Get table rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('td:not(:last-child)'); // Exclude action column
                let rowData = [];

                cols.forEach((col, index) => {
                    let text = col.innerText.trim();

                    // Ensure proper handling of special characters (e.g., Ñ)
                    text = text.normalize("NFC");

                    // Wrap values with commas inside double quotes to prevent CSV misformatting
                    if (text.includes(',') || text.includes('"')) {
                        text = `"${text.replace(/"/g, '""')}"`; // Escape double quotes
                    } else {
                        // Always wrap text in double quotes, even if it doesn't contain special characters
                        text = `"${text}"`;
                    }

                    // Remove multiple spaces
                    if (index === 0) {
                        text = text.replace(/\s+/g, ' ');
                    }

                    rowData.push(text);
                });

                csv.push(rowData.join(','));
            });

            // Call your existing download function
            downloadCSV(csv.join('\n'), filename);
        }

        function downloadCSV(csv, filename) {
            const BOM = '\uFEFF'; // Add UTF-8 BOM to fix special character encoding
            const csvFile = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function exportTableToExcel() {
            // Get the semester and academic year from hidden divs
            const semesterType = document.getElementById('semesterInfo').innerText.trim().toUpperCase();
            const academicYearName = document.getElementById('academicYearInfo').innerText.trim();

            // Construct the filename dynamically, check for null or empty semesterType and academicYearName
            let filename = 'List of Pre-Service Teachers';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.xlsx`;
            } else {
                filename += '.xlsx';
            }

            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tbody tr');
            const headers = Array.from(table.querySelectorAll('thead th')).slice(0, -1).map(th => th.innerText.trim());
            const data = [];

            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td:not(:last-child)');
                cells.forEach(cell => {
                    rowData.push(cell.innerText.trim());
                });
                data.push(rowData);
            });

            const wsData = [headers];
            data.forEach(row => {
                wsData.push(row);
            });

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
            XLSX.writeFile(wb, filename);
        }

        function printTable() {
            const table = document.getElementById('datatablesSimple').cloneNode(true);
            const actionsColumnIndex = 5;

            // Remove the "Actions" column from the cloned table
            if (table) {
                table.querySelectorAll('thead th')[actionsColumnIndex]?.remove();
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.deleteCell(actionsColumnIndex);
                });
            }

            const currentDate = new Date().toLocaleString();

            // Fetch semester and academic year from hidden elements
            let semester = document.getElementById('semesterInfo')?.textContent || '';
            const academicYear = document.getElementById('academicYearInfo')?.textContent || '';
            const firstName = document.getElementById('firstNamePrep')?.textContent || 'Unknown';
            const lastName = document.getElementById('lastNamePrep')?.textContent || 'User';

            // Capitalize the first letter of the semester value if it's available
            if (semester) {
                semester = semester.charAt(0).toUpperCase() + semester.slice(1).toLowerCase();
            }
            const fullName = `${firstName} ${lastName}`;

            let win = window.open('', '_blank');
            if (!win) {
                alert("Pop-up blocked! Please allow pop-ups for this site.");
                return;
            }

            win.document.write('<html><head><title>List of Pre-Service Teachers</title>');
            win.document.write(`
                <style>
                    @page {
                        margin-top: 5.7mm;   /* 0.38 inches */
                        margin-bottom: 7.9mm; /* 0.31 inches */
                        margin-left: 25.4mm;  /* 1 inch */
                        margin-right: 25.4mm; /* 1 inch */
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 11pt;
                        text-align: center;
                        margin: 0;
                        padding: 0;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                    }
                    th, td { 
                        padding: 6px; 
                        text-align: left; 
                        border-bottom: 1px solid #ddd; 
                        font-size: 11pt;
                        word-wrap: break-word;
                    }
                    th { 
                        background-color: #f2f2f2; 
                    }
                    .header-container { 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        text-align: center; 
                        margin-bottom: 10px; 
                    }
                    .header-container img { 
                        width: 100px; 
                        height: auto; 
                        margin-right: 15px; 
                    }
                    .text-container { 
                        display: flex; 
                        flex-direction: column; 
                        align-items: center; 
                    }
                    .header-container p { 
                        margin: 0; 
                        line-height: 1.40; 
                    }
                    .gov-text { font-family: "Century Gothic", sans-serif; font-size: 11pt; }
                    .univ-text { font-family: "Bookman Old Style", serif; font-size: 14pt; font-weight: bold; }
                    .campus-text { font-family: "Century Gothic", sans-serif; font-size: 11pt; font-weight: bold; }
                    .location-text { font-family: "Century Gothic", sans-serif; font-size: 10pt; }
                    .college-text { font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; text-align: center; }
                    .title-text { font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; text-align: center; }
                </style>
            `);

            win.document.write('</head><body>');
            win.document.write(`
                <div class="header-container" style="margin-bottom: 5px; margin-left: -115px">
                    <img id="cvsuLogo" src="../assets/img/cvsu-logo-header.jpg" alt="CVSU Logo" style="margin-bottom: 30px">
                    <div class="text-container">
                        <p class="gov-text">Republic of the Philippines</p>
                        <p class="univ-text">CAVITE STATE UNIVERSITY</p>
                        <p class="campus-text" style="margin-bottom: 5px">Don Severino de las Alas Campus</p>
                        <p class="location-text">Indang, Cavite</p>
                    </div>
                </div>
            `);
            win.document.write(`
                <div class="header-container" style="margin-bottom: 15px; justify-content: center;">
                    <p class="college-text">COLLEGE OF EDUCATION</p>
                </div>
            `);

            let title = 'List of Pre-Service Teachers';
            if (semester && academicYear) {
                title += ` for ${semester} Semester A.Y. ${academicYear}`;
            } else if (semester) {
                title += ` for ${semester} Semester`;
            } else if (academicYear) {
                title += ` A.Y. ${academicYear}`;
            }

            win.document.write(`<div class="header-container"><p class="title-text">${title}</p></div>`);
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Student Number</th><th>Name</th><th>Email Address</th><th>Program-Major</th><th>Status</th></tr></thead>');
            win.document.write('<tbody>');

            table.querySelectorAll('tbody tr').forEach(row => {
                let cells = Array.from(row.cells);
                win.document.write('<tr>' + cells.map(cell => '<td>' + cell.textContent + '</td>').join('') + '</tr>');
            });

            win.document.write('</tbody></table>');
            win.document.write(`
                <div style="margin-top: 20px; text-align: right; font-size: 10pt; font-style: italic;">
                    Date and Time Generated: ${currentDate}
                </div>
            `);

            win.document.write(`
                <div style="margin-top: 40px; text-align: left; font-size: 11pt;">
                    <p><strong>Prepared by:</strong></p>
                    <p style="margin-left: 20px;">${fullName}</p>
                </div>
            `);

            win.document.write(`
                <script>
                    window.onload = function() {
                        window.print();
                    };
                <\/script>
            `);

            win.document.write('</body></html>');
            win.document.close();
        }

    </script>
</body>
</html>