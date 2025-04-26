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

$picquery = "SELECT 
            tu.profile_picture
          FROM tbl_user tu
          WHERE tu.id = ?";

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

$sqlAnnouncements = "SELECT id, title, created_at FROM tbl_announcement WHERE audience IN ('all', 'adviser') AND isDeleted = 0 ORDER BY created_at DESC LIMIT 3";
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
                          FROM tbl_placement WHERE pre_service_teacher_id = ? AND status = 'approved'";
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
$sql_approved = "SELECT COUNT(*) as approved_count FROM tbl_attendance 
                 WHERE pre_service_teacher_id = ? AND placement_id = ? AND status = 'approved'";
$stmt_approved = $conn->prepare($sql_approved);
$stmt_approved->bind_param("ii", $pre_service_teacher_id, $placement_id);
$stmt_approved->execute();
$result_approved = $stmt_approved->get_result();
$approved_count = ($result_approved->fetch_assoc())['approved_count'] ?? 0;

// Fetch pending attendance count
$sql_pending = "SELECT COUNT(*) as pending_count FROM tbl_attendance 
                WHERE pre_service_teacher_id = ? AND placement_id = ? AND status = 'pending'";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("ii", $pre_service_teacher_id, $placement_id);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
$pending_count = ($result_pending->fetch_assoc())['pending_count'] ?? 0;
$approved_percentage = ($approved_count / $total_days) * 100;
$pending_percentage = ($pending_count / $total_days) * 100;
// Calculate remaining days
$remaining_days = $total_days - ($approved_count + $pending_count);


// Fetch placement information including school, adviser, and cooperating teacher.
$sql_placement = "SELECT p.id AS placement_id, p.school_id, p.start_date, p.end_date, s.school_name
                  FROM tbl_placement p
                  JOIN tbl_school s ON p.school_id = s.id
                  WHERE p.pre_service_teacher_id = ?";

$stmt_placement = $conn->prepare($sql_placement);
$stmt_placement->bind_param("i", $pre_service_teacher_id);
$stmt_placement->execute();
$result_placement = $stmt_placement->get_result();

$placement_found = false;
if ($result_placement->num_rows === 1) {
    $placement = $result_placement->fetch_assoc();
    $placement_id = $placement['placement_id'];
    $school_name = $placement['school_name'];
    $placement_start = $placement['start_date'];
    $placement_end = $placement['end_date'];
    $placement_found = true;

    $formatted_placement_start = date('M d, Y', strtotime($placement_start));
$formatted_placement_end = date('M d, Y', strtotime($placement_end));

}

// Fetch adviser details, if assigned
$adviser_name = null;
$sql_adviser = "SELECT u.first_name, u.middle_name, u.last_name
                FROM tbl_adviser_assignment aa
                JOIN tbl_adviser a ON aa.adviser_id = a.id
                JOIN tbl_user u ON a.user_id = u.id
                WHERE aa.placement_id = ?";
$stmt_adviser = $conn->prepare($sql_adviser);
$stmt_adviser->bind_param("i", $placement_id);
$stmt_adviser->execute();
$result_adviser = $stmt_adviser->get_result();

if ($result_adviser->num_rows === 1) {
    $adviser = $result_adviser->fetch_assoc();
    $adviser_name = $adviser['first_name'] . ' ' . $adviser['middle_name'] . ' ' . $adviser['last_name'];
}

// Fetch cooperating teacher details, if assigned
$cooperating_teacher_name = null;
$sql_cooperating_teacher = "SELECT u.first_name, u.middle_name, u.last_name
                            FROM tbl_cooperating_teacher_assignment cta
                            JOIN tbl_cooperating_teacher ct ON cta.cooperating_teacher_id = ct.id
                            JOIN tbl_user u ON ct.user_id = u.id
                            WHERE cta.placement_id = ?";
$stmt_cooperating_teacher = $conn->prepare($sql_cooperating_teacher);
$stmt_cooperating_teacher->bind_param("i", $placement_id);
$stmt_cooperating_teacher->execute();
$result_cooperating_teacher = $stmt_cooperating_teacher->get_result();

if ($result_cooperating_teacher->num_rows === 1) {
    $cooperating_teacher = $result_cooperating_teacher->fetch_assoc();
    $cooperating_teacher_name = $cooperating_teacher['first_name'] . ' ' . $cooperating_teacher['middle_name'] . ' ' . $cooperating_teacher['last_name'];
}

// Determine the placement status based on adviser and cooperating teacher assignment
if ($adviser_name && $cooperating_teacher_name) {
    $placement_status = 'approved';  // Both are assigned
} else {
    $placement_status = 'pending';   // If either or both are missing
}


    } else {
        echo "Pre-service teacher not found.";
        exit();
    }
} else {
    echo "User not found.";
    exit();
}

function getServerTime() {
    date_default_timezone_set('Asia/Manila');
    return date('h:i A');
}

function getServerDate() {
    return date('F j, Y');
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
        } .container {
            max-width: 800px;
            margin: 0 auto;
        } .time-checker {
            text-align: center;
            margin-bottom: 20px;
        } .calendar {
            text-align: center;
            margin-bottom: 20px;
            overflow-x: auto;
            white-space: nowrap;
        } .calendar table {
            width: 100%;
            max-width: 100%;
            font-size: 14px;
            border-collapse: collapse;
        } .calendar th, .calendar td {
            padding: 8px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #ccc;
        } .calendar .bg-today {
            background-color: #f0f0f0;
            font-weight: bold;
        } .time-checker {
            display: flex;
            justify-content: space-around;
        } .progress {
            background-color: black;
            border-radius: 5px;
            overflow: hidden;
        } .progress-container {
            border: 2px solid black;
            border-radius: 5px;
        } .progress-bar {
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            color: white; 
        } .status-container {
            font-size: 14px;
        } .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        } .status-color {
            width: 12px;
            height: 12px;
            margin-right: 8px;
            border-radius: 50%;
        } .approved {
            background-color: green;
        } .pending {
            background-color: yellow;
        } .remaining {
            background-color: white;
            border: 1px solid black;
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
                        <?php if ($placement_found): ?>
                        <div class="col-xl-8">
                            <div class="card mb-4" role="region" aria-labelledby="placementInformation">
                                <div class="card-header">
                                    <i class="fas fa-school me-1"></i>
                                    Placement Information
                                </div>

                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge bg-success <?= $placement_status === 'approved' ? 'badge-success' : 'badge-warning' ?> me-2"><?= htmlspecialchars(ucfirst($placement_status)); ?></span>
                                        <p class="mb-0">Placement at <?= htmlspecialchars($school_name ?? 'N/A'); ?></p>
                                    </div>
                                    <?php if (!empty($school_address)): ?>
                                        <p><strong>School Address:</strong> <?= htmlspecialchars($school_address); ?></p>
                                    <?php endif; ?>

                                    <!-- Only show adviser and cooperating teacher if both are assigned -->
                                    <?php if ($placement_status === 'approved'): ?>
                                        <p><strong>Adviser:</strong> <?= htmlspecialchars($adviser_name); ?></p>
                                        <p><strong>Cooperating Teacher:</strong> <?= htmlspecialchars($cooperating_teacher_name); ?></p>
                                    <?php endif; ?>

                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <span class="timeline-dot"></span>
                                            <p><strong>Deployment Start:</strong> <?= htmlspecialchars($formatted_placement_start); ?></p>
                                        </div>

                                        <div class="timeline-item">
                                            <span class="timeline-dot"></span>
                                            <p><strong>Deployment End:</strong> <?= htmlspecialchars($formatted_placement_end); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-line me-1"></i>
                                    Attendance Progress
                                </div>

                                <div class="card-body">
                                    <div class="progress-container">
                                        <div class="progress" style="height: 30px;">
                                            <!-- Approved attendance -->
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= number_format($approved_percentage, 2); ?>%;" 
                                                 aria-valuenow="<?= number_format($approved_percentage, 2); ?>" aria-valuemin="0" aria-valuemax="100" style="border-radius: 5px;">
                                                <?= number_format($approved_percentage, 2); ?>%
                                            </div>
                                            <!-- Pending attendance -->
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= number_format($pending_percentage, 2); ?>%;" 
                                                 aria-valuenow="<?= number_format($pending_percentage, 2); ?>" aria-valuemin="0" aria-valuemax="100" style="border-radius: 5px;">
                                                <?= number_format($pending_percentage, 2); ?>%
                                            </div>
                                            <!-- Remaining attendance -->
                                            <div class="progress-bar bg-white text-black" role="progressbar" style="width: <?= number_format($remaining_days / $total_days * 100, 2); ?>%;" 
                                                 aria-valuenow="<?= number_format($remaining_days / $total_days * 100, 2); ?>" aria-valuemin="0" aria-valuemax="100" style="border-radius: 5px;">
                                                <?= number_format($remaining_days / $total_days * 100, 2); ?>%
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
                        </div>

                        <!-- Time Reminder Card -->
                        <div class="card mb-4">
                            <div class="card-body text-center bg-secondary text-white">
                                <p class="mb-0"><i class="fas fa-bell"></i> Don't forget to time in and out today!</p>
                            </div>
                        </div>

                        <!-- Time Checker Card -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <!-- Time In -->
                                    <div class="col-md-6 mt-3 mb-3 text-center">
                                        <h5 class="mb-3">Time In</h5>
                                        <div class="mb-3"><strong id="time-in"></strong></div>
                                        <button id="time-in-btn" class="btn btn-success btn-lg" onclick="showConfirmation('time_in')">Time In</button>
                                    </div>

                                    <!-- Time Out -->
                                    <div class="col-md-6 mt-3 mb-3 text-center">
                                        <h5 class="mb-3">Time Out</h5>
                                        <div class="mb-3"><strong id="time-out"></strong></div>
                                        <button id="time-out-btn" class="btn btn-danger btn-lg" onclick="showConfirmation('time_out')" disabled>Time Out</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php else: ?>
                        <div class="alert alert-info" role="alert" aria-live="polite">
                            <p><strong>Status:</strong> You are not yet placed in a school. Please check back later for updates or contact your adviser for assistance.</p>
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

    <!-- Bootstrap JS and other scripts -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>

    <script>
        // Pass the pre_service_teacher_id and placement_id to JavaScript
        const pre_service_teacher_id = <?php echo json_encode($pre_service_teacher_id); ?>;
        const placement_id = <?php echo json_encode($placement_id); ?>;
        const server_time = <?php echo json_encode(getServerTime()); ?>;
    </script>

    <!-- Script for Time Tracker -->
    <script>
        function showConfirmation(actionType) {
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
            document.getElementById('time-in').textContent = time;
            document.getElementById('time-out-btn').disabled = false;
            document.getElementById('time-in-btn').disabled = true;

            sendAttendanceData({
                action: 'time_in',
                time_in: time
            });
        }

        function timeOutConfirmed() {
            const time = <?php echo json_encode(getServerTime()); ?>;
            document.getElementById('time-out-btn').disabled = true;
            document.getElementById('time-in-btn').disabled = true;

            sendAttendanceData({
                action: 'time_out',
                time_out: time
            });
        }

        function sendAttendanceData(data) {
            data.placement_id = placement_id;
            data.pre_service_teacher_id = pre_service_teacher_id;

            fetch('attendance_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                console.log(result); // Add this to check if you're getting the correct data
                if (result.success) {
                    console.log('Attendance recorded successfully');
                    if (data.action === 'time_in') {
                        // Set time-in after it's successfully recorded
                        document.getElementById('time-in').textContent = result.time_in || data.time_in;
                        document.getElementById('time-out-btn').disabled = false;
                        document.getElementById('time-in-btn').disabled = true;
                    } else if (data.action === 'time_out') {
                        // Set both time-in and time-out after the time-out action
                        document.getElementById('time-in').textContent = result.time_in;
                        document.getElementById('time-out').textContent = result.time_out;
                        document.getElementById('time-out-btn').disabled = true;

                    }
                } else {
                    console.log('Failed to record attendance');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next',
                    center: 'title',
                    right: ''
                },
                events: [
                    // Sample events data (replace with your actual data)
                    {
                        title: 'Attendance Recorded',
                        start: '2024-07-15',
                        color: '#4caf50'
                    },
                    // Add more events as needed
                ],
                eventClick: function(info) {
                    alert('Attendance details for: ' + info.event.startStr);
                    // Replace with your logic to show attendance details
                }
            });

            calendar.render();

            // Update calendar title on view change
            calendarEl.querySelector('#calendar-prev').addEventListener('click', function() {
                calendar.prev();
                updateCalendarTitle(calendar);
            });

            calendarEl.querySelector('#calendar-next').addEventListener('click', function() {
                calendar.next();
                updateCalendarTitle(calendar);
            });

            updateCalendarTitle(calendar);
        });


        document.addEventListener('DOMContentLoaded', () => {
            // Fetch current attendance status when the page loads
            checkAttendanceStatus();
        });

        function checkAttendanceStatus() {
            fetch('attendance_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'check_status',
                    pre_service_teacher_id: pre_service_teacher_id,
                    placement_id: placement_id
                })
            })

            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (result.status === 'time_in') {
                        // User has only timed in, disable time-in button and enable time-out button
                        document.getElementById('time-in-btn').disabled = true;
                        document.getElementById('time-out-btn').disabled = false;
                        document.getElementById('time-in').textContent = result.time_in;
                    } else if (result.status === 'time_out') {
                        // User has both time in and time out, display both and disable buttons
                        document.getElementById('time-in-btn').disabled = true;
                        document.getElementById('time-out-btn').disabled = true;
                        document.getElementById('time-in').textContent = result.time_in;
                        document.getElementById('time-out').textContent = result.time_out;
                    }
                }
            })

            .catch(error => {
                console.error('Error fetching attendance status:', error);
            });
        }
    </script>

    <!-- Essential scripts -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>