<?php 
include 'includes/auth.php';

// Fetch notifications from the database
$user_id = $_SESSION['user_id'];
$sqlNotifications = "SELECT * FROM tbl_notification WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sqlNotifications);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultNotifications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Notifications - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .notification-item {
            border-bottom: 1px solid #ddd;
            padding: 1rem;
        }
        .notification-time {
            font-size: 0.875rem;
            color: #777;
        }
        @media (max-width: 600px) {
            .notification-buttons {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
            }
            .notification-buttons button {
                margin-top: 0.5rem;
            }
            .notification-buttons button .text {
                display: none;
            }
            .notification-buttons button .icon {
                display: inline;
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
                    <h1 class="mt-4">Notifications</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Notifications</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-bell"></i> Notifications List
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php if ($resultNotifications->num_rows > 0): ?>
                                    <?php while ($notification = $resultNotifications->fetch_assoc()): ?>
                                        <li class="list-group-item notification-item d-flex justify-content-between align-items-center <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                                            <!-- Notification message and date -->
                                            <a class="flex-grow-1 text-decoration-none text-dark fw-medium" href="<?php echo htmlspecialchars($notification['link']); ?>" data-notification-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                                <small><?php echo htmlspecialchars($notification['message']); ?></small>
                                                <br>
                                                <span class="text-muted small"><?php echo timeAgo($notification['created_at']); ?></span>
                                            </a>
                                            <!-- Delete button -->
                                            <button type="button" class="btn btn-sm btn-danger ms-2 me-2 delete-notification-btn" data-bs-toggle="modal" data-bs-target="#deleteNotificationModal" data-notification-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-lg-12">
                                        <div>No notifications found.</div>
                                    </div>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>

    document.querySelectorAll('.notification-item').forEach(function (notificationItem) {
        notificationItem.addEventListener('click', function () {
            const notificationId = this.getAttribute('data-id');
            const link = this.getAttribute('data-link');

            if (!notificationId) {
                // No ID, just navigate
                window.location.href = link;
                return;
            }

            // Mark as read via AJAX
            fetch('functions/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Notification marked as read.');
                    // Optionally, update the UI (e.g., remove bold styling)
                    this.classList.remove('unread');
                } else {
                    console.error('Failed to mark notification as read:', data.message);
                }
                // Navigate to the link regardless of marking success
                window.location.href = link;
            })
            .catch(error => {
                console.error('Error:', error);
                // Navigate even if there's an error
                window.location.href = link;
            });
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
    </script>
</body>
</html>