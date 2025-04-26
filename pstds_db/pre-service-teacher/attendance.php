<?php
session_start();
include '../connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Error: No user ID found in session. Please log in again.');
}

$user_id = $_SESSION['user_id'];

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

// Step 1: Retrieve the full name of the logged-in user from tbl_user
$sql_user = "SELECT CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name 
             FROM tbl_user WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

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
    } else {
        echo "Pre-service teacher not found.";
        exit();
    }
} else {
    echo "User not found.";
    exit();
}

function getServerTime() {
    date_default_timezone_set('Asia/Manila'); // Set timezone if needed
    return date('h:i A'); // Returns time in 12-hour format with AM/PM
}

function getServerDate() {
    return date('F j, Y'); // Example: October 18, 2024
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Attendance management system">
    <meta name="author" content="Your Name">
    <title>Attendance</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/poppins.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.css" rel="stylesheet">

    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .current-time {
            font-size: 1.5rem;
            font-weight: bold;
        }
        /* Custom styles for FullCalendar */
        .calendar-panel {
            background-color: #ffffff;
            border-radius: 8px;
            margin-bottom: 20px; /* Added margin bottom for spacing */
            max-height: 600px; /* Limit the height of calendar panel */
            overflow-y: auto; /* Add scroll when content exceeds height */
        }

        .calendar-header {
            background-color: #f0f0f0;
            padding: 10px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            font-weight: bold;
        }

        .calendar-body {
            padding: 10px;
        }

        #calendar {
            max-width: 100%;
            background-color: #ffffff;
            border-radius: 8px;
        }

        .fc-day {
            background-color: #f9f9f9;
        }

        .fc-event {
            background-color: #4caf50;
            border-color: #4caf50;
            color: #ffffff;
        }

        .fc-event:hover {
            background-color: #388e3c;
            border-color: #388e3c;
            color: #ffffff;
        }

        /* Adjust width of the calendar */
        @media (min-width: 992px) {
            #calendar {
                width: calc(100% - 40px); /* Adjust as needed */
                margin-left: 20px; /* Add spacing */
            }
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
                    <h1 class="mt-5 h3">Attendance</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Attendance</li>
                    </ol>
                    <?php if ($placement_found): ?>
                    <div class="row">
                        <!-- Attendance Panel -->
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h2 class="card-title">Attendance</h2>
                                    <p class="current-time" id="current-time"></p>
                                    <div class="user-info mb-3">
                                    <p>Name: <?php echo htmlspecialchars($user_name); ?></p>

                                    <p>Time In: <span id="time-in"></span></p>
                                    <p>Time Out: <span id="time-out"></span></p>
                                        <p>Date: <span id="today-date"></span></p>
                                    </div>
                                    <button id="time-in-btn" class="btn btn-success mt-1" onclick="timeIn()">Time In</button>
                                    <button id="time-out-btn" class="btn btn-danger mt-1" onclick="timeOut()" disabled>Time Out</button>
                                    <button id="journal-btn" class="btn btn-primary mt-1" onclick="redirectToJournal()" style="display: none;">Write Journal</button>
                                </div>
                                <?php else: ?>
                                    <div class="col-12 text-center">
                                        <h5 class="text-danger">You are still not placed.</h5>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Calendar Panel -->
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="calendar-panel">
                                    <div class="calendar-body">
                                        <div id="calendar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Bootstrap JS and other scripts -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <!-- FullCalendar core -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.0/main.min.js"></script>
    <!-- Moment.js (required by FullCalendar) -->
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script>
        // Pass the pre_service_teacher_id and placement_id to JavaScript
        const pre_service_teacher_id = <?php echo json_encode($pre_service_teacher_id); ?>;
        const placement_id = <?php echo json_encode($placement_id); ?>;
        const server_time = <?php echo json_encode(getServerTime()); ?>;
    </script>
    <!-- Script for Time Tracker -->
<script>
    function getCurrentDateTime() {
        const now = new Date();
        const options = { year: 'numeric', month: 'long' };
        const formattedDate = now.toLocaleDateString('en-US', options);
        document.getElementById('today-date').textContent = formattedDate;
    }

    function updateCurrentTime() {
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        document.getElementById('current-time').textContent = time;
    }

    function timeIn() {
    const time = <?php echo json_encode(getServerTime()); ?>;
    document.getElementById('time-in').textContent = time;
    document.getElementById('time-out-btn').disabled = false;
    document.getElementById('time-in-btn').disabled = true;

    // Call the attendance function with 'time_in' action
    sendAttendanceData({
        action: 'time_in',
        time_in: time
    });
}

function timeOut() {
    const time = <?php echo json_encode(getServerTime()); ?>;
    document.getElementById('time-out-btn').disabled = true;
    document.getElementById('time-in-btn').disabled = true;
    document.getElementById('journal-btn').style.display = 'inline-block'; // Show the journal button

    // Call the attendance function with 'time_out' action
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
                document.getElementById('journal-btn').style.display = 'inline-block'; // Show the journal button
            }
        } else {
            console.log('Failed to record attendance');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

    function addAttendanceLog(action, time) {
        // Here you can add functionality to log attendance
    }

    function redirectToJournal() {
        window.location.href = 'add-journal-entry.php';
    }

    // Call the function immediately on page load
    document.addEventListener('DOMContentLoaded', () => {
        getCurrentDateTime();
        updateCurrentTime();
    });

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

    function updateCalendarTitle(calendar) {
        var title = calendar.view.title;
        document.getElementById('calendar-title').innerText = title;
    }
    document.addEventListener('DOMContentLoaded', () => {
        // Fetch current attendance status when the page loads
        checkAttendanceStatus();

        // Your existing code here, such as initializing the date and time
        getCurrentDateTime();
        updateCurrentTime();
    });

    // Existing function to get the current date
    function getCurrentDateTime() {
        const now = new Date();
        const options = { year: 'numeric', month: 'long' };
        const formattedDate = now.toLocaleDateString('en-US', options);
        document.getElementById('today-date').textContent = formattedDate;
    }

    // Existing function to update the current time
    function updateCurrentTime() {
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        document.getElementById('current-time').textContent = time;
    }

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
                document.getElementById('time-in').textContent = result.time_in;  // Display time_in
            } else if (result.status === 'time_out') {
                // User has both time in and time out, display both and disable buttons
                document.getElementById('time-in-btn').disabled = true;
                document.getElementById('time-out-btn').disabled = true;
                document.getElementById('time-in').textContent = result.time_in;  // Display time_in
                document.getElementById('time-out').textContent = result.time_out;  // Display time_out
            }
        }
    })
    .catch(error => {
        console.error('Error fetching attendance status:', error);
    });
}
</script>
</body>
</html>