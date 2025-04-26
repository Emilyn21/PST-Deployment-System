<?php
include 'includes/auth.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Error: No user ID found in session. Please log in again.');
}

$user_id = $_SESSION['user_id'];

// Step 1: Retrieve the full name of the logged-in user from tbl_user
$sql_user = "SELECT CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name 
             FROM tbl_user WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

$currentYear = date('Y');

// Fetch the profile picture
$picquery = "SELECT tu.profile_picture FROM tbl_user tu WHERE tu.id = ?";
if ($stmt = $conn->prepare($picquery)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $profile_picture = $row['profile_picture'];
    }
    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}

// Fetch the latest announcements
$sqlAnnouncements = "SELECT id, title, created_at 
                     FROM tbl_announcement 
                     WHERE audience IN ('all', 'adviser') AND isDeleted = 0 
                     ORDER BY created_at DESC LIMIT 1";
$resultAnnouncements = $conn->query($sqlAnnouncements);

if ($result_user->num_rows === 1) {
    $user = $result_user->fetch_assoc();
    $full_name = $user['full_name'];

    // Step 2: Retrieve the pre_service_teacher_id from tbl_pre_service_teacher
    $sql_teacher = "SELECT id AS pre_service_teacher_id 
                    FROM tbl_pre_service_teacher WHERE user_id = ?";
    $stmt_teacher = $conn->prepare($sql_teacher);
    $stmt_teacher->bind_param("i", $user_id);
    $stmt_teacher->execute();
    $result_teacher = $stmt_teacher->get_result();

    if ($result_teacher->num_rows === 1) {
        $teacher = $result_teacher->fetch_assoc();
        $pre_service_teacher_id = $teacher['pre_service_teacher_id'];

        // Step 3: Retrieve the placement_id from tbl_placement
        $sql_placement = "SELECT id AS placement_id 
                          FROM tbl_placement 
                          WHERE pre_service_teacher_id = ? AND status IN ('pending', 'approved') AND tbl_placement.isDeleted = 0
                          ORDER BY created_at DESC LIMIT 1";  // Order by latest created_at and get the most recent placement

        $stmt_placement = $conn->prepare($sql_placement);
        $stmt_placement->bind_param("i", $pre_service_teacher_id);
        $stmt_placement->execute();
        $result_placement = $stmt_placement->get_result();

        $placement_found = false;
        if ($result_placement->num_rows === 1) {
            $placement = $result_placement->fetch_assoc();
            $placement_id = $placement['placement_id'];
            $placement_found = true;
        }

        // Set the total required attendance days
        $total_days = 60;

        // Fetch approved attendance count
        $sql_approved = "SELECT COUNT(*) as approved_count 
                         FROM tbl_attendance 
                         WHERE pre_service_teacher_id = ? AND placement_id = ? AND status = 'approved'";
        $stmt_approved = $conn->prepare($sql_approved);
        $stmt_approved->bind_param("ii", $pre_service_teacher_id, $placement_id);
        $stmt_approved->execute();
        $result_approved = $stmt_approved->get_result();
        $approved_count = ($result_approved->fetch_assoc())['approved_count'] ?? 0;

        // Fetch pending attendance count
        $sql_pending = "SELECT COUNT(*) as pending_count 
                        FROM tbl_attendance 
                        WHERE pre_service_teacher_id = ? AND placement_id = ? AND status = 'pending'";
        $stmt_pending = $conn->prepare($sql_pending);
        $stmt_pending->bind_param("ii", $pre_service_teacher_id, $placement_id);
        $stmt_pending->execute();
        $result_pending = $stmt_pending->get_result();
        $pending_count = ($result_pending->fetch_assoc())['pending_count'] ?? 0;

        // Calculate attendance percentages and remaining days
        $approved_percentage = min(($approved_count / $total_days) * 100, 100);
        $pending_percentage = min(($pending_count / $total_days) * 100, 100);

        $remaining_days = $total_days - ($approved_count + $pending_count);
    } else {
        echo "Pre-service teacher not found.";
        exit();
    }
} else {
    echo "User not found.";
    exit();
}

// Helper functions
function getServerTime() {
    date_default_timezone_set('Asia/Manila');
    return date('h:i A');
}

function getServerDate() {
    return date('F j, Y');
}

if (!empty($time_out)) {
    $reminderText = "";
} elseif (!empty($time_in)) {
    $reminderText = "";
} else {
    $reminderText = "";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Dashboard</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;  
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .time-checker {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
        }

        .progress {
            background-color: black;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-container {
            border: 2px solid black;
            border-radius: 5px;
        }

        .progress-bar {
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            color: white;
        }

        .status-container {
            font-size: 14px;
        }

        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .status-color {
            width: 12px;
            height: 12px;
            margin-right: 8px;
            border-radius: 50%;
        }

        .approved {
            background-color: green;
        }

        .pending {
            background-color: yellow;
        }

        .remaining {
            background-color: white;
            border: 1px solid black;
        }
    .timeline {
        border-left: 2px solid #dee2e6;
        padding-left: 1rem;
        margin: 1rem 0;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1rem;
        padding-left: 1rem;
    }

    .timeline-dot {
        width: 10px;
        height: 10px;
        background-color: #007bff;
        border-radius: 50%;
        position: absolute;
        left: -5px;
        top: 5px;
    }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-5 h3">Dashboard</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Pre-Service Teacher Dashboard</li>
                    </ol>
                    <div class="row">
                        <?php 
                        $query = "
                            SELECT 
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
                                ) AS student_name,
                                tcs.school_name AS school_placement,
                                CASE 
                                    WHEN COALESCE(tcs.street, tcs.barangay, tcs.city, tcs.province) IS NULL THEN 'Pending'
                                    ELSE TRIM(
                                        CONCAT(
                                            COALESCE(tcs.street, ''), 
                                            CASE WHEN tcs.street IS NOT NULL AND tcs.barangay IS NOT NULL THEN ', ' ELSE ' ' END,
                                            COALESCE(tcs.barangay, ''), 
                                            CASE WHEN tcs.barangay IS NOT NULL AND tcs.city IS NOT NULL THEN ', ' ELSE ' ' END,
                                            COALESCE(tcs.city, ''), 
                                            CASE WHEN tcs.city IS NOT NULL AND tcs.province IS NOT NULL THEN ', ' ELSE ' ' END,
                                            COALESCE(tcs.province, '')
                                        )
                                    )
                                END AS school_address,
                                TRIM(
                                    CASE 
                                        WHEN tadvu.first_name IS NULL AND tadvu.last_name IS NULL THEN ''
                                        ELSE CONCAT(
                                            tadvu.first_name, 
                                            CASE 
                                                WHEN tadvu.middle_name IS NOT NULL AND tadvu.middle_name != '' THEN CONCAT(' ', tadvu.middle_name) 
                                                ELSE '' 
                                            END, 
                                            ' ', 
                                            tadvu.last_name
                                        )
                                    END
                                ) AS adviser_name,
                                TRIM(
                                    CASE 
                                        WHEN tctu.first_name IS NULL AND tctu.last_name IS NULL THEN ''
                                        ELSE CONCAT(
                                            tctu.first_name, 
                                            CASE 
                                                WHEN tctu.middle_name IS NOT NULL AND tctu.middle_name != '' THEN CONCAT(' ', tctu.middle_name) 
                                                ELSE '' 
                                            END, 
                                            ' ', 
                                            tctu.last_name
                                        )
                                    END
                                ) AS cooperating_teacher_name,
                                tpl.start_date,
                                tpl.end_date,
                                tpst.placement_status AS pst_status,
                                tpl.status AS placement_status,
                                tpl.date_approved
                            FROM tbl_pre_service_teacher tpst
                            LEFT JOIN tbl_placement tpl ON tpst.id = tpl.pre_service_teacher_id
                            LEFT JOIN tbl_user tu ON tpst.user_id = tu.id
                            LEFT JOIN tbl_school tcs ON tpl.school_id = tcs.id
                            LEFT JOIN tbl_adviser_assignment taa ON taa.placement_id = tpl.id
                            LEFT JOIN tbl_adviser ta ON taa.adviser_id = ta.id
                            LEFT JOIN tbl_user tadvu ON ta.user_id = tadvu.id
                            LEFT JOIN tbl_cooperating_teacher_assignment tcta ON tcta.placement_id = tpl.id
                            LEFT JOIN tbl_cooperating_teacher tct ON tcta.cooperating_teacher_id = tct.id
                            LEFT JOIN tbl_user tctu ON tct.user_id = tctu.id
                            WHERE tu.id = ?
                            ORDER BY 
                                FIELD(tpl.status, 'approved') DESC, 
                                tpl.created_at DESC
                            LIMIT 1";

                        if ($stmt = $conn->prepare($query)) {
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($row = $result->fetch_assoc()) {
                                $student_name = $row['student_name'];
                                $school_placement = $row['school_placement'];
                                $school_address = $row['school_address'];
                                $adviser_name = $row['adviser_name'];
                                $cooperating_teacher_name = $row['cooperating_teacher_name'];
                                $placement_start = !empty($row['start_date']) ? date('M j, Y', strtotime($row['start_date'])) : 'N/A';
                                $placement_end = !empty($row['end_date']) ? date('M j, Y', strtotime($row['end_date'])) : 'N/A';
                                $placement_status = $row['placement_status'] ?: 'N/A';
                            }

                            $stmt->close();
                        } else {
                            echo "Error: " . $conn->error;
                        }

                        $badgeClass = ($placement_status === 'approved') 
                            ? 'bg-success' 
                            : (($placement_status === 'pending') ? 'bg-warning' : 'bg-secondary');
                        ?>

                        <?php if ($placement_found): ?>
                            <div class="col-xl-8">
                                <section id="placement-info">
                                    <div class="card mb-4" role="region" aria-labelledby="placementInformation">
                                        <div class="card-header">
                                            <i class="fas fa-school me-1"></i>
                                            Placement Information
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="badge <?= $badgeClass; ?> me-2"><?= htmlspecialchars(ucfirst($placement_status)); ?></span>
                                                <p class="mb-0">Placement at <?= htmlspecialchars($school_placement ?? 'N/A'); ?></p>
                                            </div>
                                            <?php if (!empty($school_address)): ?>
                                                <p><strong>School Address:</strong> <?= htmlspecialchars($school_address); ?></p>
                                            <?php endif; ?>
                                            <?php if ($placement_status === 'approved'): ?>
                                                <p><strong>Adviser:</strong> <?= htmlspecialchars($adviser_name); ?></p>
                                                <p><strong>Cooperating Teacher:</strong> <?= htmlspecialchars($cooperating_teacher_name); ?></p>
                                            <?php endif; ?>
                                            <div class="timeline">
                                                <div class="timeline-item">
                                                    <span class="timeline-dot"></span>
                                                    <p><strong>Placement Start:</strong> <?= htmlspecialchars($placement_start); ?></p>
                                                </div>
                                                <div class="timeline-item">
                                                    <span class="timeline-dot"></span>
                                                    <p><strong>Placement End:</strong> <?= htmlspecialchars($placement_end); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <!-- Always Show Announcement Panel -->
                            <div class="col-xl-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-bullhorn me-2"></i>Recent Announcements
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <?php if ($resultAnnouncements->num_rows > 0): ?>
                                                <?php while ($announcement = $resultAnnouncements->fetch_assoc()): ?>
                                                    <li>
                                                        <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                                        <small>
                                                            (<?php
                                                            $announcementDate = strtotime($announcement['created_at']);
                                                            $announcementYear = date('Y', $announcementDate);
                                                            echo date('M j', $announcementDate); // Always display month and day
                                                            if ($announcementYear !== $currentYear) {
                                                                echo ", $announcementYear"; // Add the year only if it's not the current year
                                                            }
                                                            echo ', ' . date('g:i A', $announcementDate);
                                                            ?>)
                                                        </small>
                                                    </li>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <li>No announcements found.</li>
                                            <?php endif; ?>
                                        </ul>
                                        <a href="announcement.php" class="btn btn-primary">View All Announcements</a>
                                    </div>
                                </div>

                                <!-- Reminder Panel (Only for Approved Placements) -->
                                <?php if ($placement_status === 'approved'): ?>
                                    <div class="card mb-4">
                                        <div class="card-header bg-warning text-dark">
                                            <i class="fas fa-bell me-2"></i>Reminder
                                        </div>
                                        <div class="card-body text-center">
                                            <p class="mb-0">
                                                <i id="reminder-icon" class="fas fa-sign-in-alt text-primary" 
                                                   data-bs-toggle="tooltip"
                                                   title="<?php echo !empty($time_out) ? 'Journal pending' : (!empty($time_in) ? 'Time out is pending' : 'You have not timed in yet'); ?>"
                                                   style="font-size: 24px;"></i><br>
                                                <span id="time-reminder-text" class="ms-2"><?php echo $reminderText; ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                            <div class="col-xl-8">
                                <section id="time-checker">
                                    <!-- Time Checker Card -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex align-items-center">
                                            <i class="fas fa-clock me-2"></i>
                                            <span>Time Checker</span>
                                            <span class="ms-auto text-muted" id="current-date-time">
                                                <?php echo date('F j, Y'); // PHP to show today's date ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- Time In -->
                                                <div class="col-md-6 text-center">
                                                    <div class="card border-success shadow-sm">
                                                        <div class="card-body">
                                                            <h5 class="text-success mb-3">Time In</h5>
                                                            <div class="mb-3">
                                                                <strong id="time-in">N/A</strong>
                                                            </div>
                                                            <button id="time-in-btn" class="btn btn-success btn-lg" onclick="showConfirmation('time_in')">Time In</button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Time Out -->
                                                <div class="col-md-6 text-center">
                                                    <div class="card border-danger shadow-sm">
                                                        <div class="card-body">
                                                            <h5 class="text-danger mb-3">Time Out</h5>
                                                            <div class="mb-3">
                                                                <strong id="time-out">N/A</strong>
                                                            </div>
                                                            <button id="time-out-btn" class="btn btn-danger btn-lg" onclick="showConfirmation('time_out')" disabled>Time Out</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                            <div class="col-xl-4">
                                <!-- Time Reminder Card -->
                                <section id="attendance-progress">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <i class="fas fa-chart-line me-1"></i>
                                            Attendance Progress
                                        </div>
                                        <div class="card-body">
                                            <div class="progress-container" aria-live="polite">
                                                <div class="progress" style="height: 20px;"> <!-- Reduced height for smaller progress bars -->
                                                    <!-- Approved attendance -->
                                                    <div class="progress-bar bg-success progress-bar-sm" role="progressbar"
                                                         aria-label="Approved Attendance <?= number_format($approved_percentage, 2); ?>%" 
                                                         data-bs-toggle="tooltip" 
                                                         title="Approved Attendance: <?= $approved_count ?> day(s) out of <?= $total_days ?> days. Pending: <?= $pending_count ?> day(s), Remaining: <?= $remaining_days ?> day(s)."
                                                         style="width: <?= number_format($approved_percentage, 2); ?>%;">
                                                        <span class="progress-text"><?= number_format($approved_percentage, 2); ?>%</span>
                                                    </div>

                                                    <!-- Pending attendance -->
                                                    <div class="progress-bar bg-warning progress-bar-sm" role="progressbar" 
                                                         aria-label="Pending Attendance <?= number_format($pending_percentage, 2); ?>%" 
                                                         data-bs-toggle="tooltip" 
                                                         title="Pending Attendance: <?= $pending_count ?> day(s) out of <?= $total_days ?> days. Approved: <?= $approved_count ?> day(s), Remaining: <?= $remaining_days ?> day(s)."
                                                         style="width: <?= number_format($pending_percentage, 2); ?>%;" 
                                                         aria-valuenow="<?= number_format($pending_percentage, 2); ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <span class="progress-text"><?= number_format($pending_percentage, 2); ?>%</span>
                                                    </div>

                                                    <!-- Remaining attendance -->
                                                    <div class="progress-bar bg-white text-black progress-bar-sm" role="progressbar" 
                                                         aria-label="Remaining Attendance <?= number_format($remaining_days / $total_days * 100, 2); ?>%" 
                                                         data-bs-toggle="tooltip" 
                                                         title="Remaining Attendance: <?= $remaining_days ?> day(s) out of <?= $total_days ?> days. Approved: <?= $approved_count ?> day(s), Pending: <?= $pending_count ?> day(s)."
                                                         style="width: <?= number_format($remaining_days / $total_days * 100, 2); ?>%;" 
                                                         aria-valuenow="<?= number_format($remaining_days / $total_days * 100, 2); ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <span class="progress-text"><?= number_format($remaining_days / $total_days * 100, 2); ?>%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="mt-3">
                                                <strong>Total Days:</strong> <?= $total_days ?><br>
                                                <div class="status-container">
                                                    <div class="status-item">
                                                        <span class="status-color approved"></span>
                                                        <span class="status-label">Approved: <?= $approved_count ?> day(s)</span>
                                                    </div>
                                                    <div class="status-item">
                                                        <span class="status-color pending"></span>
                                                        <span class="status-label">Pending: <?= $pending_count ?> day(s)</span>
                                                    </div>
                                                    <div class="status-item">
                                                        <span class="status-color remaining"></span>
                                                        <span class="status-label">Remaining: <?= $remaining_days ?> day(s)</span>
                                                    </div>
                                                </div>
                                            </p>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="alert alert-info" role="alert" aria-live="polite">
                                    <p><strong>Status:</strong> You are not yet placed in a school. Please check back later for updates or contact your adviser for assistance.</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmTimeModal" role="dialog" tabindex="-1" aria-labelledby="confirmTimeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel"><i class="fas fa-edit"></i> Confirm Action</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.body.classList.toggle('sb-sidenav-toggled');
        });
    </script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        // Pass the pre_service_teacher_id and placement_id to JavaScript
        const pre_service_teacher_id = <?php echo json_encode($pre_service_teacher_id); ?>;
        const placement_id = <?php echo json_encode($placement_id); ?>;
        const server_time = <?php echo json_encode(trim(getServerTime())); ?>;


        function showConfirmation(actionType) {
            console.log("Server Time:", server_time);

            let message = actionType === 'time_in'
                ? 'Are you sure you want to <strong>Time In</strong>? This action cannot be undone.'
                : 'Are you sure you want to <strong>Time Out</strong>? This action cannot be undone.';

            document.getElementById("confirmMessage").innerHTML = message;

            // Store the action type for execution after confirmation
            document.getElementById("confirmActionBtn").setAttribute("data-action", actionType);

            // Show the modal
            $('#confirmTimeModal').modal('show');
        }

        // Event listener for the confirm button inside the modal
        document.getElementById("confirmActionBtn").addEventListener("click", function () {
            let actionType = this.getAttribute("data-action");
            $('#confirmTimeModal').modal('hide');

            if (actionType === 'time_in') {
                timeInConfirmed();
            } else if (actionType === 'time_out') {
                timeOutConfirmed();
            }
        });

        function timeInConfirmed() {
            const time = <?php echo json_encode(getServerTime()); ?>;
            console.log("Raw timestamp from PHP:", time); // Debugging log

            const formattedTime = formatTime(time);
            document.getElementById('time-in').textContent = formattedTime; // Show formatted time immediately
            document.getElementById('time-out-btn').disabled = false;
            document.getElementById('time-in-btn').disabled = true;

            sendAttendanceData({
                action: 'time_in',
                time_in: time // Send raw timestamp to backend
            });
        }

        function timeOutConfirmed() {
            const time = <?php echo json_encode(getServerTime()); ?>;
            console.log("Raw timestamp from PHP:", time); // Debugging log

            const formattedTime = formatTime(time);
            document.getElementById('time-out').textContent = formattedTime; // Show formatted time immediately
            document.getElementById('time-out-btn').disabled = true;
            document.getElementById('time-in-btn').disabled = true;

            // Delay update to ensure DOM change is reflected
            requestAnimationFrame(updateReminderText);
            
            sendAttendanceData({
                action: 'time_out',
                time_out: time // Send raw timestamp to backend
            });
        }


        function formatTime(timestamp) {
            console.log("Raw timestamp from PHP:", timestamp); // Debugging

            // Ensure timestamp is valid before processing
            if (!timestamp || typeof timestamp !== "string" || timestamp.trim() === "") {
                console.warn("Received an invalid timestamp:", timestamp);
                return ""; // Return empty string instead of "Invalid Time"
            }

            let timePart = timestamp.split(" ")[1]; // Extract HH:MM:SS
            if (!timePart) return "";

            let timeArray = timePart.split(":");
            if (timeArray.length < 2) return "";

            let hour = parseInt(timeArray[0], 10);
            let minute = timeArray[1];

            if (isNaN(hour) || isNaN(parseInt(minute, 10))) {
                console.error("Time extraction failed! Check timestamp format.");
                return "";
            }

            let amPm = hour < 12 ? "AM" : "PM";
            let hour12 = hour % 12 || 12;

            return `${hour12}:${minute} ${amPm}`;
        }


        function sendAttendanceData(data) {
            data.placement_id = placement_id;
            data.pre_service_teacher_id = pre_service_teacher_id;

            fetch('attendance_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())  // ✅ Correctly parse as JSON
            .then(result => {
                console.log("Parsed JSON response:", result);

                if (result.success) {
                    console.log('Attendance recorded successfully');
                    if (data.action === 'time_in') {
                        document.getElementById('time-in').textContent = result.time_in || data.time_in;
                        document.getElementById('time-out-btn').disabled = false;
                        document.getElementById('time-in-btn').disabled = true;
                    } else if (data.action === 'time_out') {
                        if (result.time_in) {
                            document.getElementById('time-in').textContent = formatTime(result.time_in);
                        }
                        if (result.time_out) {
                            document.getElementById('time-out').textContent = formatTime(result.time_out);
                        }
                        document.getElementById('time-out-btn').disabled = true;
                    }
                } else {
                    console.log('Failed to record attendance:', result.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
            });
        }


document.addEventListener('DOMContentLoaded', () => {
    checkAttendanceStatus();
});

function checkAttendanceStatus() {
    fetch('attendance_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'check_status',
            pre_service_teacher_id: pre_service_teacher_id,
            placement_id: placement_id
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            let timeInText = "N/A";
            let timeOutText = "N/A";

                    if (result.status === 'time_in') {
                        document.getElementById('time-in-btn').disabled = true;
                        document.getElementById('time-out-btn').disabled = false;

                        if (result.time_in) {
                            timeInText = formatTime(result.time_in);
                            document.getElementById('time-in').textContent = timeInText;
                        }
                    } else if (result.status === 'time_out') {
                        document.getElementById('time-in-btn').disabled = true;
                        document.getElementById('time-out-btn').disabled = true;

                        if (result.time_in) {
                            timeInText = formatTime(result.time_in);
                            document.getElementById('time-in').textContent = timeInText;
                        }
                        if (result.time_out) {
                            timeOutText = formatTime(result.time_out);
                            document.getElementById('time-out').textContent = timeOutText;
                        }
                    }
            updateReminderText(timeInText, timeOutText);
        }
    })
    .catch(error => {
        console.error('Error fetching attendance status:', error);
    });
}

function updateReminderText(timeInText, timeOutText) {
    let reminderText = "";
    let iconElement = document.getElementById("reminder-icon");

    // ✅ Remove previous FontAwesome classes
    iconElement.classList.remove("fa-book", "fa-sign-out-alt", "fa-sign-in-alt");

    if (timeOutText !== "N/A") {
        reminderText = `Don't forget to <a href="manage-journal-entry.php" class="text-primary">write</a> your journal!`;
        iconElement.classList.add("fa-book"); // Journal icon
        iconElement.setAttribute("title", "Journal pending");
    } else if (timeInText !== "N/A") {
        reminderText = "Don't forget to time out!";
        iconElement.classList.add("fa-sign-out-alt"); // Time-out icon
        iconElement.setAttribute("title", "Time out is pending");
    } else {
        reminderText = "Don't forget to time in if you have classes for today!";
        iconElement.classList.add("fa-sign-in-alt"); // Time-in icon
        iconElement.setAttribute("title", "You have not timed in yet");
    }

    // ✅ Use innerHTML instead of textContent to render the link
    document.getElementById("time-reminder-text").innerHTML = reminderText;

    // ✅ Ensure the 'fas' class is always present
    iconElement.classList.add("fas", "text-primary");

    // ✅ Reinitialize Bootstrap Tooltip (Fixes Tooltip Not Updating)
    let tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipEl => new bootstrap.Tooltip(tooltipEl));
}


        // JavaScript to update the time dynamically
        function updateTime() {
            const now = new Date();
            const options = { hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: true };
            const timeString = new Intl.DateTimeFormat('en-US', options).format(now);
            document.getElementById('current-date-time').innerHTML = 
                `<?php echo date('F j, Y'); ?>, ${timeString}`;
        }

        // Call the function every second to keep the time updated
        setInterval(updateTime, 1000);
        updateTime(); // Initial call to set the time immediately
    </script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
