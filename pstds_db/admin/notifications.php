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
                            <i class="fas fa-bell"></i>
                            Notifications List
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <!-- Example Notifications -->
                                <li class="list-group-item notification-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="#" class="text-decoration-none text-dark fw-medium">New user registered: John Doe</a>
                                        <br>
                                        <time class="notification-time text-muted" datetime="2024-07-17T12:00:00">3 hours ago</time>
                                    </div>
                                    <div class="notification-buttons">
                                        <button class="btn btn-primary btn-sm mark-as-read">
                                            <span class="text d-none d-md-inline"><i class="fas fa-check"></i> Mark as Read</span>
                                            <span class="icon"><i class="fas fa-check"></i></span>
                                        </button>
                                        <button class="btn btn-danger btn-sm delete-notification">
                                            <span class="text d-none d-md-inline"><i class="fas fa-trash-alt"></i> Delete</span>
                                            <span class="icon"><i class="fas fa-trash-alt"></i></span>
                                        </button>
                                    </div>
                                </li>
                                <li class="list-group-item notification-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="#" class="text-decoration-none text-dark fw-medium">Notification message 2</a>
                                        <br>
                                        <time class="notification-time text-muted" datetime="2024-07-16T15:00:00">1 day ago</time>
                                    </div>
                                    <div class="notification-buttons">
                                        <button class="btn btn-primary btn-sm mark-as-read">
                                            <span class="text d-none d-md-inline"><i class="fas fa-check"></i> Mark as Read</span>
                                            <span class="icon"><i class="fas fa-check"></i></span>
                                        </button>
                                        <button class="btn btn-danger btn-sm delete-notification">
                                            <span class="text d-none d-md-inline"><i class="fas fa-trash-alt"></i> Delete</span>
                                            <span class="icon"><i class="fas fa-trash-alt"></i></span>
                                        </button>
                                    </div>
                                </li>
                                <!-- Additional notifications go here -->
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
        // JavaScript for handling delete notification and mark as read buttons
        document.addEventListener('DOMContentLoaded', function() {
            var deleteButtons = document.querySelectorAll('.delete-notification');
            var markAsReadButtons = document.querySelectorAll('.mark-as-read');

            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this notification?')) {
                        // Proceed with delete action
                        var notification = this.closest('.list-group-item');
                        notification.remove();
                    }
                });
            });

            markAsReadButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    // Your mark as read logic (could be AJAX request to update status)
                    var notification = this.closest('.list-group-item');
                    notification.style.opacity = '0.6'; // Example: visually mark as read
                    button.disabled = true; // Example: disable the button after marking as read
                });
            });
        });
    </script>
</body>
</html>
