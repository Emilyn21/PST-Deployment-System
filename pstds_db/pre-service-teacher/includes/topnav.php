<?php
include '../connect.php';

if (basename($_SERVER['PHP_SELF']) == 'topnav.php') {
    header('Location: ../index.php');
    exit();
}

function getNotifications($user_id) {
    global $conn;
    
    $sql = "
        SELECT id, message, link, is_read, created_at
        FROM tbl_notification
        WHERE user_id = ? AND status = 'active'
        ORDER BY created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getActiveCount($user_id) {
    global $conn;

    $sqlActiveCount = "
        SELECT COUNT(*) AS active_count
        FROM tbl_notification
        WHERE user_id = ? AND status = 'active'
    ";
    $stmtActiveCount = $conn->prepare($sqlActiveCount);
    $stmtActiveCount->bind_param("i", $user_id);
    $stmtActiveCount->execute();
    return $stmtActiveCount->get_result()->fetch_assoc()['active_count'];
}

function getUnreadCount($user_id) {
    global $conn;

    $sqlUnreadCount = "
        SELECT COUNT(*) AS unread_count
        FROM tbl_notification
        WHERE user_id = ? AND status = 'active' AND is_read = 0
    ";
    $stmtUnreadCount = $conn->prepare($sqlUnreadCount);
    $stmtUnreadCount->bind_param("i", $user_id);
    $stmtUnreadCount->execute();
    return $stmtUnreadCount->get_result()->fetch_assoc()['unread_count'];
}


date_default_timezone_set('Asia/Manila'); // Set to your timezone

function timeAgo($time) {
    // Check if $time is not null before processing
    if (!$time) {
        return "Invalid time"; // Or handle the error appropriately
    }

    $timeDiff = time() - strtotime($time);

    if ($timeDiff < 60) {
        return "Just now";
    } elseif ($timeDiff < 3600) {
        $minutes = floor($timeDiff / 60);
        return $minutes == 1 ? "1 minute ago" : "$minutes minutes ago";
    } elseif ($timeDiff < 86400) {
        $hours = floor($timeDiff / 3600);
        return $hours == 1 ? "1 hour ago" : "$hours hours ago";
    } elseif ($timeDiff < 172800) { // If less than 2 days ago, it’s yesterday
        return "Yesterday at " . date("g:i A", strtotime($time)); // Show time for yesterday
    } else {
        return formattedDate($time); // For anything older than yesterday, show the date
    }
}

function formattedDate($time) {
    // Check if the time is a valid date before formatting
    if (!$time) {
        return "Invalid date"; // Handle invalid dates gracefully
    }

    $dateYear = date("Y", strtotime($time));
    $currentYear = date("Y");

    if ($dateYear == $currentYear) {
        // If it's the current year, exclude the year from the output
        return date("M j \\a\\t g:i A", strtotime($time));
    } else {
        // If it's not the current year, include the year
        return date("M j, Y \\a\\t g:i A", strtotime($time));
    }
}


// Check if $notification exists and 'created_at' is set
$notifications = getNotifications($user_id);

// Debugging: Check if notifications are available
if (!empty($notifications)) {
    $notification = $notifications[0];  // Use the first notification
    if (isset($notification['created_at']) && !empty($notification['created_at'])) {
        $notificationDate = $notification['created_at'];

    }
}

$unreadCount = getUnreadCount($user_id);
$activeCount = getActiveCount($user_id);

function getUserDetails($conn, $user_id) {
    $sql = "
        SELECT 
            CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(last_name, '')) AS fullname, 
            profile_picture, 
            role
        FROM tbl_user
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error in preparing statement: " . $conn->error);
        return [
            'fullname' => 'Unknown User',
            'profile_picture' => '../assets/img/default-image.jpg',
            'role' => 'Unknown Role',
        ];
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        return [
            'fullname' => $user_data['fullname'] ?: 'Unknown User',
            'profile_picture' => !empty($user_data['profile_picture']) 
                ? base64_encode($user_data['profile_picture']) 
                : null, // Leave null if not available
            'role' => mapRoleName($user_data['role'] ?? 'Unknown Role'),
        ];
    } else {
        return [
            'fullname' => 'Unknown User',
            'profile_picture' => null, // Leave null if no profile picture
            'role' => 'Unknown Role',
        ];
    }
}

function mapRoleName($role) {
    switch ($role) {
        case 'cooperating_teacher':
            return 'Cooperating Teacher';
        case 'pre-service teacher':
            return 'Pre-Service Teacher';
        case 'school_admin':
            return 'School Administrator';
        case 'adviser':
            return 'Adviser'; // Capitalize first letter
        case 'admin':
            return 'Administrator';
        default:
            return 'Unknown Role';
    }
}

if (isset($_SESSION['user_id'])) {
    try {
        $user_id = $_SESSION['user_id'];
        $user_details = getUserDetails($conn, $user_id);
        $user_name = $user_details['fullname'];
        $user_role = $user_details['role'];
    } catch (Exception $e) {
        echo $e->getMessage();
        exit();
    }
} else {
    header('Location: ../login.php');
    exit();
}

function getMostRecentActiveAcademicYear() {
    global $conn;

    // Query to fetch the most recent active academic year
    $sql = "
        SELECT academic_year_name
        FROM tbl_academic_year
        WHERE isDeleted = 0 AND status = 'active'
        ORDER BY end_date DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['academic_year_name']; // Return the most recent academic year name
    } else {
        return null; // Return null if no active academic year is found
    }
}

function getActiveSemesterWithAcademicYear() {
    global $conn;

    // Query to fetch the most recent active semester with the academic year details
    $sql = "
        SELECT s.id AS semester_id, s.type, s.start_date, s.end_date, s.academic_year_id, a.academic_year_name
        FROM tbl_semester s
        INNER JOIN tbl_academic_year a ON s.academic_year_id = a.id
        WHERE s.status = 'active' AND s.isDeleted = 0
        ORDER BY s.start_date DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc(); // Return the most recent active semester and its academic year details
    } else {
        return null; // Return null if no active semester is found
    }
}

// Fetch the active semester and the most recent academic year
$activeSemester = getActiveSemesterWithAcademicYear();
$recentAcademicYear = getMostRecentActiveAcademicYear();

if ($activeSemester) {
    // If there's an active semester, use its academic year
    $semesterType = strtoupper($activeSemester['type']); // Example: 'FIRST', 'SECOND', 'MIDYEAR'
    $academicYear = $activeSemester['academic_year_name']; // Academic Year name
} elseif ($recentAcademicYear) {
    // If there's no active semester but a recent academic year
    $semesterType = 'No Active Semester';
    $academicYear = $recentAcademicYear;
} else {
    // If there's neither an active semester nor an active academic year
    $semesterType = 'No Active Semester';
    $academicYear = 'No Active Academic Year';
}


?>
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark topnav-custom">
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm ms-lg-2 ms-2 order-0 order-lg-0 me-lg-3 me-3 sidebar-toggle-custom" title="sidebar" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div>
        <img src="../assets/img/cvsu-logo.png" 
             alt="CvSU Logo" 
             style="width: 30px; height: 30px; object-fit: cover; background-color: transparent; border-radius: 50%;">
    </div>

    <div class="navbar-brand ps-3">
        <span>CvSU Pre-Service Teacher Placement System</span>
        <br>
        <small class="text-muted" id="academicYear">
            <?php if ($academicYear === 'No Active Academic Year'): ?>
                <span class="text-danger">NO ACTIVE ACADEMIC YEAR AND SEMESTER
                </span>
            <?php else: ?>
                <?= htmlspecialchars($academicYear, ENT_QUOTES, 'UTF-8'); ?>:
                <?php if ($semesterType !== 'No Active Semester'): ?>
                    <?= htmlspecialchars($semesterType, ENT_QUOTES, 'UTF-8'); ?> SEMESTER
                <?php else: ?>
                    <span class="text-danger">NO ACTIVE SEMESTER</span>
                <?php endif; ?>
            <?php endif; ?>
        </small>
    </div>

    <!-- Navbar Search-->
    <form class="d-none d-md-inline-block form-inline ms-2 me-0 me-md-3 my-2 my-md-0" id="searchForm">
        <div class="input-group">
            <input class="form-control" id="searchInput" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
            <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
        </div>
    </form>
    
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-2 me-lg-4">
        <li class="nav-item dropdown bell-custom">
            <a class="nav-link dropdown-toggle dropdown-toggle-custom" id="notificationDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell fa-fw"></i>
                <?php if ($unreadCount > 0) { ?>
                    <span class="badge bg-danger rounded-pill"><?= $unreadCount; ?></span>
                <?php } ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                <h6 class="dropdown-header">Notifications (<?= $activeCount; ?>)</h6>
                <li><hr class="dropdown-divider"></li>
                <!-- Scrollable area for notifications -->
                <div class="scrollable-container" style="max-height: 200px">
                    <?php if (!empty($notifications)) { ?>
                        <?php foreach ($notifications as $notification) { 
                            $notificationDate = strtotime($notification['created_at']);
                            $currentYear = date('Y');
                            $notificationYear = date('Y', $notificationDate);
                            // Check if the year is today's year, and format accordingly
                            if ($notificationYear == $currentYear) {
                                $formattedDate = date('M d, h:i A', $notificationDate);
                            } else {
                                $formattedDate = date('M d, Y h:i A', $notificationDate);
                            }
                        ?>
                        <li class="d-flex justify-content-between align-items-center <?= $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                            <!-- Notification message and date -->
                            <a class="dropdown-item flex-grow-1" href="<?= htmlspecialchars($notification['link']); ?>" data-notification-id="<?= htmlspecialchars($notification['id']); ?>">
                                <small><?= htmlspecialchars($notification['message']); ?></small>
                                <br>
                                <span class="text-muted small"><?= timeAgo($notification['created_at']); ?></span>
                            </a>
                            <!-- Delete button (1/4 width on the right) -->
                            <button type="button" class="btn btn-sm btn-danger ms-2 me-2 delete-notification-btn" data-bs-toggle="modal" data-bs-target="#deleteNotificationModal" data-notification-id="<?= htmlspecialchars($notification['id']); ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                        <?php } ?>
                    <?php } else { ?>
                        <li class="text-muted text-center p-2"><small>No notifications</small></li>
                    <?php } ?>
                </div>
                <li><hr class="dropdown-divider"></li>
                <?php if (!empty($notifications)) { ?>
                    <li class="text-center">
                        <button class="btn btn-sm btn-link text-decoration-none" id="markAllRead">Mark All as Read</button>
                    </li>
                    <!--li><a class="dropdown-item text-center" href="notifications.php">View All</a></li-->
                <?php } ?>
            </ul>
        </li>
        <li class="nav-item dropdown account-custom">
            <a class="nav-link dropdown-toggle dropdown-toggle-custom d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" >
                <!-- Profile Picture -->
                <?php if (!empty($profile_picture)) { ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($profile_picture); ?>" 
                         class="rounded-circle me-2" 
                         alt="Profile Picture" 
                         style="width: 30px; height: 30px; object-fit: cover;">
                <?php } else { ?>
                    <img src="../assets/img/default-image.jpg" 
                         class="rounded-circle me-2" 
                         alt="Default Profile Picture" 
                         style="width: 30px; height: 30px; object-fit: cover;">
                <?php } ?>
                <!-- Display Only First Name -->
                <span class="d-none d-md-inline">
                    <?php 
                        $first_name = explode(' ', trim($user_name))[0]; // Extract first name
                        echo htmlspecialchars($first_name); 
                    ?>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end " aria-labelledby="navbarDropdown"  style="width: 250px;">
                <div class="scrollable-container" style="max-height: 200px">
                <li class="py-2 px-3 d-flex align-items-center">
                    <!-- Profile Picture -->
                    <div>
                        <?php if (!empty($profile_picture)) { ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($profile_picture); ?>" 
                                 class="rounded-circle" 
                                 alt="Profile Picture" 
                                 style="width: 40px; height: 40px; object-fit: cover;">
                        <?php } else { ?>
                            <img src="../assets/img/default-image.jpg" 
                                 class="rounded-circle" 
                                 alt="Default Profile Picture" 
                                 style="width: 40px; height: 40px; object-fit: cover;">
                        <?php } ?>
                    </div>
                    <!-- Name and Role -->
                    <div class="ms-3">
                        <strong class="d-block mb-0"><?= htmlspecialchars($user_name); ?></strong>
                        <span class="text-muted small"><?= htmlspecialchars($user_role); ?></span>
                    </div>
                </li>
                    <li><hr class="dropdown-divider"></li>
                    <div class="account-scrollable-container">
                    <li><a class="dropdown-item" href="account.php">Account</a></li>
                    <!--li class="d-md-none"><a class="dropdown-item" href="notifications.php">Notifications</a></li>
                    <li><a class="dropdown-item" href="settings.php">Settings</a></li-->
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                </div>
            </ul>
        </li>
    </ul>
</nav>

<style>
    .navbar-brand {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sidebar-toggle-custom {
        margin-left: 15px; /* Add space to the left */
        padding: 0; /* Adjust padding as needed */
        font-size: 1.25rem; /* Adjust icon size */
    }
    .notification-dropdown {
        width: 250px; /* Adjust the width as needed */
    }

    .scrollable-container {
        overflow-y: auto;  /* Make it scrollable */
    }

    .notification-dropdown .dropdown-item {
        white-space: normal;
    }

    .notification-dropdown .dropdown-header {
        font-size: 1rem;
        font-weight: bold;
    }

    .account-custom, .bell-custom {
        margin-right: -10px;
    }

    .unread {
        font-weight: bold;
        color: #000; /* Text color */
        background-color: rgba(0, 0, 0, 0.1); /* Light shaded background */
    }
    li:hover {
        background-color: rgba(0, 0, 0, 0.0750); /* Add background shade on hover */
    }
    .dropdown-item:active {
        background-color: var(--bs-secondary); /* Bootstrap secondary color */
        color: #fff; /* Ensure text is visible */
    }
    /* Fix top navigation sticky behavior */
    .topnav-custom {
        position: fixed; /* Ensures it stays at the top */
        top: 0; /* Align it at the very top */
        width: 100%; /* Make it span the full width of the page */
        z-index: 1050; /* Ensure it's above all other elements */
        height: 75px; /* Adjust the height as needed */
        display: flex;
        align-items: center; /* Vertically center content */
        padding: 0 1rem;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Optional shadow for separation */
    }

    /* Increase height for the topnav branding and content alignment */
    .topnav-custom .navbar-brand {
        font-size: 1rem; /* Adjust text size */
        line-height: 1.2; /* Adjust spacing between lines */
    }

    /* Ensure no conflicts with the sidebar */
    .sidebar {
        z-index: 1040; /* Lower than the topnav */
    }
</style>

<!-- Confirmation Modal -->
<div class="modal fade" id="deleteNotificationModal" tabindex="-1" aria-labelledby="deleteNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteNotificationModalLabel"><i class="fas fa-trash"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this notification?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteNotificationForm" action="functions/delete-notification.php" method="POST" class="d-inline">
                    <input type="hidden" id="notificationId" name="notification_id" value="">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Add event listener for modal open to set the notification ID
    document.querySelectorAll('.delete-notification-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var notificationId = this.getAttribute('data-notification-id');
            document.getElementById('notificationId').value = notificationId;
        });
    });

    document.querySelectorAll('.dropdown-item[data-notification-id]').forEach(function(notificationLink) {
        notificationLink.addEventListener('click', function(event) {
            event.preventDefault(); // Stop the default navigation

            const notificationId = this.getAttribute('data-notification-id');
            const href = this.getAttribute('href'); // Get the original href

            if (!notificationId) {
                // If there's no notification ID, just proceed with navigation
                window.location.href = href;
                return;
            }

            // Make the AJAX request to mark the notification as read
            fetch('functions/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Notification marked as read.');
                    // Navigate to the original link after marking as read
                    window.location.href = href;
                } else {
                    console.error('Failed to mark notification as read:', data.message);
                    // Navigate even if marking fails
                    window.location.href = href;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Navigate even if there's an error
                window.location.href = href;
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const markAllReadButton = document.getElementById('markAllRead');

        if (markAllReadButton) {
            markAllReadButton.addEventListener('click', function () {
                fetch('functions/mark-all-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ notification_id: 'all' })  // Send the 'notification_id' to indicate marking all as read
                })
                .then(response => response.text())  // Read the response as text for debugging
                .then(data => {
                    console.log('Response:', data);  // Log the raw response for debugging
                    try {
                        const jsonData = JSON.parse(data);  // Try parsing the response as JSON
                        if (jsonData.success) {
                            document.querySelectorAll('.unread').forEach(el => el.classList.remove('unread'));
                            const unreadBadge = document.querySelector('.badge.bg-danger');
                            if (unreadBadge) unreadBadge.remove();
                        } else {
                            alert(jsonData.message || 'Failed to mark notifications as read.');
                        }
                    } catch (err) {
                        console.error('Error parsing JSON:', err);
                        alert('An unexpected error occurred. Please try again.');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An unexpected error occurred. Please try again.');
                });
            });
        }
    });

    document.getElementById('btnNavbarSearch').addEventListener('click', function() {
        var searchQuery = document.getElementById('searchInput').value;
        if (searchQuery) {
            window.location.href = 'search.php?q=' + encodeURIComponent(searchQuery);
        }
    });

    // Allow pressing "Enter" to submit the form
    document.getElementById('searchInput').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            document.getElementById('btnNavbarSearch').click();
        }
    });
</script>
