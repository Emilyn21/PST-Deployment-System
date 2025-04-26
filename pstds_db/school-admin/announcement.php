<?php 
include 'includes/auth.php';

// Function to generate SQL queries for announcements
function buildAnnouncementQuery($conn, $filterType, $searchQuery, $isCount = false, $limit = null, $offset = null) {
    // Start the base query
    $baseQuery = "SELECT " . ($isCount ? "COUNT(*)" : "*") . " FROM tbl_announcement WHERE audience IN ('all', 'school_admin') AND isDeleted = 0";
    
    // Conditions array and parameters
    $conditions = [];
    $params = [];

    // Add filter type condition
    if ($filterType !== 'all') {
        $conditions[] = "announcement_type = ?";
        $params[] = $filterType;
    }
    
    // Add search query condition
    if (!empty($searchQuery)) {
        $conditions[] = "title LIKE ?";
        $params[] = '%' . trim($searchQuery) . '%'; // Trim search query to remove unnecessary whitespace
    }

    // Combine conditions into WHERE clause
    if (!empty($conditions)) {
        $baseQuery .= " AND " . implode(" AND ", $conditions);
    }

    // Order by created_at descending
    $baseQuery .= " ORDER BY created_at DESC";

    // Add LIMIT and OFFSET for pagination
    if ($limit !== null) {
        $baseQuery .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
    }

    // Prepare the query
    $stmt = $conn->prepare($baseQuery);

    // Bind parameters dynamically
    if (!empty($params)) {
        // Use appropriate parameter types for binding (i.e., 's' for strings, 'i' for integers)
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    return $stmt;
}

// Parameters for filter, search, and pagination
$filterType = isset($_GET['filterType']) ? $_GET['filterType'] : 'all';
$searchQuery = isset($_GET['searchQuery']) ? $_GET['searchQuery'] : '';
$limit = 6;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Generate count query
$stmtCount = buildAnnouncementQuery($conn, $filterType, $searchQuery, true);
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalAnnouncements = $resultCount->fetch_row()[0];

// Generate announcements query
$stmtAnnouncements = buildAnnouncementQuery($conn, $filterType, $searchQuery, false, $limit, $offset);
$stmtAnnouncements->execute();
$resultAnnouncements = $stmtAnnouncements->get_result();

// Check if we're handling a "Load More" request
if ($offset > 0) {
    if ($resultAnnouncements && $resultAnnouncements->num_rows > 0) { // Check if result is set
        while ($announcement = $resultAnnouncements->fetch_assoc()) {
            echo '<div class="col-md-12">';
            echo '<div class="card announcement-card">';
            echo '<div class="card-body">';
            echo '<div class="announcement-header">';
            echo '<h2 class="card-title mb-0">' . htmlspecialchars($announcement['title']) . '</h2>';

            if ($announcement['announcement_type'] === 'important') {
                echo '<span class="badge badge-danger ml-2 d-inline-flex align-items-center justify-content-start important-badge" style="margin-left: 10px; gap: 5px; font-size: 1rem; padding: 0.25rem 0.5rem;">
                        <i class="fas fa-exclamation-circle mr-2"></i> Important
                      </span>';
            }

            echo '</div>';
            echo '<p class="card-text">' . nl2br(htmlspecialchars($announcement['content'])) . '</p>';
            // Simplified date format with time
            echo '<p class="announcement-date">Posted on: ' . date('M j, Y, g:i A', strtotime($announcement['created_at'])) . '</p>';

            if (!empty($announcement['file_url'])) {
                echo '<div class="attachment">';
                echo '<a href="../uploads/a/' . htmlspecialchars($announcement['file_url']) . '" target="_blank">View Attachment</a>';
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';

        }
        exit;
    } else {
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Announcements page" />
    <meta name="author" content="" />
    <title>Announcements - School Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        } 
        .announcement-card {
            border-left: 5px solid #007bff;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        } 
        .announcement-date {
            font-size: 0.9rem;
            color: #6c757d;
        } 
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        } 
        .search-filter {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        } 
        .search-filter input {
            flex: 1;
            max-width: 100%;
        } 
        .search-filter select {
            width: auto;
            min-width: 100px;
            margin-left: 10px;
        } 
        .search-filter button {
            margin-left: 10px;
        }
        .card-body {
            padding: 1.25rem;
        } 
        .attachment a {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 30ch;
        }
        .badge-danger {
            background-color: #dc3545;
            font-weight: bold;
            color: #fff;
            padding: 0.4em 0.7em;
            font-size: 1rem; /* Increase font size */
            display: inline-flex;
            align-items: center;
            border-radius: 4px;
        }
        .announcement-header {
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
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
                    <h1 class="mt-5 h3" id="main-heading">Announcements</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Announcements</li>
                    </ol>
                    <div class="search-filter">
                        <form method="GET" action="announcement.php" class="d-flex flex-wrap align-items-center gap-2">
                            <div class="flex-grow-1">
                                <input type="text" name="searchQuery" class="form-control" placeholder="Search announcements" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Search</button>
                            <div class="flex-grow-1">
                                <select class="form-select" id="filterType" name="filterType" onchange="this.form.submit()">
                                    <option value="all" <?php if ($filterType === 'all') echo 'selected'; ?>>All</option>
                                    <option value="important" <?php if ($filterType === 'important') echo 'selected'; ?>>Important</option>
                                    <option value="reminder" <?php if ($filterType === 'reminder') echo 'selected'; ?>>Reminder</option>
                                    <option value="general" <?php if ($filterType === 'general') echo 'selected'; ?>>General</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="row" id="announcement-container">
                        <?php if ($resultAnnouncements->num_rows > 0): ?>
                            <?php while ($announcement = $resultAnnouncements->fetch_assoc()): ?>
                                <div class="col-md-12">
                                    <div class="card announcement-card">
                                        <div class="card-body">
                                            <div class="announcement-header">
                                                <h2 class="card-title mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h2>
                                                <?php if ($announcement['announcement_type'] === 'important'): ?>
                                                    <span class="badge badge-danger ml-2 d-inline-flex align-items-center justify-content-start important-badge" style="margin-left: 10px; gap: 5px; font-size: 1rem; padding: 0.25rem 0.5rem;">
                                                        <i class="fas fa-exclamation-circle mr-2"></i> Important
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="card-text">
                                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                            </p>
                                            <p class="announcement-date">Posted on: <?php echo date('M j, Y, g:i A', strtotime($announcement['created_at'])); ?></p>
                                            <?php if (!empty($announcement['file_url'])): ?>
                                                <?php 
                                                // Extract the file name from the file URL
                                                $fileName = basename($announcement['file_url']); 
                                                
                                                // Truncate the file name if it's too long
                                                $maxLength = 30; // Adjust as needed
                                                if (strlen($fileName) > $maxLength) {
                                                    $fileName = substr($fileName, 0, $maxLength) . '...'; 
                                                }
                                                ?>
                                                <div class="attachment">
                                                    <a href="../uploads/a/<?php echo htmlspecialchars($announcement['file_url']); ?>" target="_blank">View Attachment
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-lg-12">
                                <div>No announcements found.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Load More Button -->
                    <div class="d-flex justify-content-center mt-4" id="load-more-container">
                        <?php if ($offset + $resultAnnouncements->num_rows < $totalAnnouncements): ?>
                            <button class="btn btn-primary" id="load-more-btn">Load More</button>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
       <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorMessage">An error occurred. Please try again.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.js"></script>
    <script src="../js/datatables-simple-demo.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let offset = 6; // Start from the 7th announcement
            const limit = 6; // Number of announcements to load each time
            const totalAnnouncements = <?php echo $totalAnnouncements; ?>;
            const loadMoreBtn = document.getElementById('load-more-btn');
            const announcementContainer = document.getElementById('announcement-container');

            // Hide Load More button if no more announcements
            if (offset >= totalAnnouncements) {
                loadMoreBtn.style.display = 'none';
            }

            loadMoreBtn.addEventListener('click', function () {
                const filterType = '<?php echo $filterType; ?>';
                const searchQuery = '<?php echo htmlspecialchars($searchQuery); ?>';
                const url = `announcement.php?offset=${offset}&filterType=${filterType}&searchQuery=${searchQuery}`;

                // Fetch new announcements
                fetch(url)
                    .then(response => response.text())
                    .then(data => {
                        // Insert new announcements and apply styles immediately
                        announcementContainer.insertAdjacentHTML('beforeend', data);
                        applyAttachmentStyles();

                        // Update offset and hide Load More if all announcements are loaded
                        offset += limit;
                        if (offset >= totalAnnouncements) {
                            loadMoreBtn.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading more announcements:', error);
                        showModalError('An error occurred while loading more announcements. Please try again.');
                    });
            });
        });

        // Function to apply the attachment styles to new elements
        function applyAttachmentStyles() {
            document.querySelectorAll('.attachment a').forEach(function(link) {
                // Ensure text overflow ellipsis for attachments
                link.style.whiteSpace = 'nowrap';
                link.style.overflow = 'hidden';
                link.style.textOverflow = 'ellipsis';
                link.style.maxWidth = '30ch';
            });
        }

        // Show error modal with custom message
        function showModalError(message) {
            document.getElementById('errorMessage').textContent = message;
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        }

        // Initial style application
        applyAttachmentStyles();
    </script>
</body>
</html>