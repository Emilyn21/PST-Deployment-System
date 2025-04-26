<?php
include 'includes/auth.php';

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

$stmt = $conn->prepare("SELECT DATE(tatt.time_in) AS date, tatt.id, tj.id AS journal_id, tj.content AS journal_content
    FROM tbl_attendance tatt 
    JOIN tbl_placement tpl ON tatt.placement_id = tpl.id
    JOIN tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
    JOIN tbl_user tu ON tpst.user_id = tu.id
    LEFT JOIN tbl_journal tj ON tj.attendance_id = tatt.id
    WHERE tu.id = ? AND tatt.status = 'approved'
    ORDER BY tatt.time_in ASC"); // Order by time_in from oldest to newest

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$counter = 1; // Initialize counter
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />

    <title>Manage Journal Entries</title>

    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        } @media (max-width: 576px) {
            .flex-wrap-nowrap {
                flex-wrap: wrap!important;
            }
        } .btn-action {
            margin-top: 0.2rem;
        } .form-group {
            margin-bottom: 0.15rem;
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
                    <h1 class="mt-5 h3" id="main-heading">Manage Journal Entries</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Journal Entries</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                View and manage your journal entries for each approved attendance below.
                            </p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Daily Journal
                        </div>
                                                    
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV('programs.csv')" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel('programs.xlsx')" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Attendance and Daily Journal Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Day</th>
                                        <th scope="col" role="columnheader">Date</th>
                                        <th scope="col" role="columnheader">Actions</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td role="cell"><?php echo $counter++; ?></td>
                                            <td role="cell"><?php echo date("F j, Y", strtotime($row['date'])); ?></td>
                                            <td role="cell">                                            
                                                <button class="btn btn-sm btn-warning btn-action" 
                                                    onclick="openEditModal(
                                                        <?php echo $row['id']; ?>,
                                                        '<?= htmlspecialchars(date('M j, Y', strtotime($row['date'])), ENT_QUOTES, 'UTF-8'); ?>',
                                                        '<?php echo $counter - 1; ?>', // Pass the Day number
                                                        <?php echo isset($row['journal_id']) ? $row['journal_id'] : 'null'; ?>,
                                                        '<?php echo isset($row['journal_content']) ? htmlspecialchars($row['journal_content'], ENT_QUOTES, 'UTF-8') : ''; ?>'
                                                    )">
                                                    <i class="fa fa-edit"></i>
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

<!-- Edit Journal Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editJournalForm" action="process-manage-journal-entry.php" method="POST">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel"><i class="fa fa-edit"></i> Edit Journal Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="attendance_id" id="editAttendanceId">
                    <input type="hidden" name="journal_id" id="editJournalId">

                    <div class="row mb-3" role="group" aria-labelledby="journalDetailsLabel">
                        <div class="col-12 col-md-6">
                            <h6 id="journalDetailsLabel" role="heading" aria-level="3"><strong>Day:</strong> <span id="editJournalDay"></span></h6>
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 role="heading" aria-level="3"><strong>Date:</strong> <span id="editJournalDate"></span></h6>
                        </div>
                    </div>

                    <!-- Journal Content Field -->
                    <div class="mb-3">
                        <label for="editJournalContent" class="form-label fw-bold">Journal Content</label>
                        <textarea class="form-control border border-secondary" name="content" id="editJournalContent" rows="4" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer justify-content-between">
                    <!-- Export Button -->
                    <button type="button" class="btn btn-success" id="exportJournalBtn">
                        <i class="fas fa-download"></i> Export
                    </button>
                    
                    <!-- Close and Save Buttons -->
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="update">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Action Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="editSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editSuccessModalLabel"><i class="fa fa-check-circle"></i> Action Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-secondary" data-bs-dismiss="modal">Okay</a>
                </div>
            </div>
        </div>
    </div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"> <!-- This must exist -->
                Error message will appear here.
            </div>
        </div>
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
                        noRows: "No placements have been added for the current active academic year."
                    },
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100, -1]
                });

                setTimeout(() => {
                    document.querySelectorAll(".datatable-dropdown option").forEach(option => {
                        if (option.value == "-1") {
                            option.textContent = "All";
                        }
                    });
                }, 100);
            }
        });

        document.getElementById('editJournalForm').onsubmit = function (e) {
            e.preventDefault(); // Prevent form submission

            const formData = new FormData(this);

            fetch('functions/process-journal-entry.php', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                var myModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                if (myModal) myModal.hide(); // Hide the edit modal

                if (data.status === 'success') {
                    let successModal = document.getElementById('successModal');
                    if (successModal) {
                        successModal.querySelector('.modal-body').textContent = data.message;
                        let modal = new bootstrap.Modal(successModal);
                        modal.show();
                        successModal.addEventListener('hidden.bs.modal', function() {
                            window.location.reload();
                        });
                    }
                } else {
                    let errorModal = document.getElementById('errorModal');
                    if (errorModal) {
                        errorModal.querySelector('.modal-body').textContent = data.message || "An error occurred.";
                        let modal = new bootstrap.Modal(errorModal);
                        modal.show();
                    }
                }
            })
            .catch(() => {
                let errorModal = document.getElementById('errorModal');
                if (errorModal) {
                    errorModal.querySelector('.modal-body').textContent = "A network error occurred.";
                    new bootstrap.Modal(errorModal).show();
                }
            });
        };

        function openEditModal(id, date, dayNumber, journal_id, journal_content) {
            document.getElementById('editAttendanceId').value = id;
            document.getElementById('editJournalId').value = journal_id;
            document.getElementById('editJournalDay').textContent = `${dayNumber}`;
            document.getElementById('editJournalDate').textContent = date;
            document.getElementById('editJournalContent').value = journal_content || '';

            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }

        document.getElementById("exportJournalBtn").addEventListener("click", function() {
            // Get the values of the journal fields
            var date = document.getElementById("editJournalDate").textContent.trim(); // Use .textContent instead of .value
            var content = document.getElementById("editJournalContent").value.trim(); 

            // Set a default title since "editJournalTitle" is missing
            var title = "Journal Entry " + (date ? `(${date})` : "");

            // Create a text file content
            var textFileContent = "Date: " + date + "\n\n";
            textFileContent += "Content:\n" + content;

            // Create a Blob (file) from the text content
            var blob = new Blob([textFileContent], { type: "text/plain" });

            // Create an invisible anchor element to trigger download
            var link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = title + ".txt"; // Use the title as the filename

            // Trigger the download by clicking the link
            link.click();
        });

    </script>

</body>
</html>