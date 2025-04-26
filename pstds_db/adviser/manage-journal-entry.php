<?php 
session_start();
include '../connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: ../login.php');
    exit();
}

// Check the logged-in user's role
$user_id = $_SESSION['user_id'];
$sqlRoleCheck = "SELECT role FROM tbl_user WHERE id = ?";
$stmt = $conn->prepare($sqlRoleCheck);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_role = $user['role'];

// Redirect based on the user's role
switch ($user_role) {
    case 'admin':
        header('Location: ../admin/index.php');
        exit();
    case 'pre-service teacher':
        header('Location: ../pre-service-teacher/index.php');
        exit();
    case 'school_admin':
        header('Location: ../school-admin/index.php');
        exit();
    case 'cooperating_teacher':
        header('Location: ../cooperating-teacher/index.php');
        exit();
    case 'adviser':
        // Allow access to this page if the user is an adviser
        break;
    default:
        // Redirect to login or error page if the role is unknown
        header('Location: ../login.php');
        exit();
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
    <title>Manage Journal Entries - Adviser</title>
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
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Manage Journal Entries</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Manage Journal Entries</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                Manage journal entries and generate reports.
                            </p>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Journal Entries
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV('journal_entries.csv')" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel('journal_entries.xlsx')" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Student Number</th>
                                        <th>Name</th>
                                        <th>Journal Title</th>
                                        <th>Journal Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Student Number</th>
                                        <th>Name</th>
                                        <th>Journal Title</th>
                                        <th>Journal Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </tfoot>
                                <!-- Fetch all journal entries where isDeleted = 0 -->
                                <tbody>
                                    <tr>
                                        <td>123456</td>
                                        <td>John Doe</td>
                                        <td>My First Journal</td>
                                        <td>2024-07-10</td>
                                        <td>Reviewed</td>
                                        <td>
                                            <button class="btn btn-sm btn-info btn-action" onclick="openViewModal(123456, 'John Doe', 'My First Journal', '2024-07-10', 'Reviewed')"><i class="fa fa-eye"></i> View</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">View Journal Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Student Number:</strong> <span id="viewStudentNumber"></span></p>
                    <p><strong>Student Name:</strong> <span id="viewStudentName"></span></p>
                    <p><strong>Journal Title:</strong> <span id="viewJournalTitle"></span></p>
                    <p><strong>Journal Date:</strong> <span id="viewJournalDate"></span></p>
                    <p><strong>Status:</strong> <span id="viewStatus"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Journal Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteJournalTitle"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
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
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });

        function openViewModal(studentNumber, studentName, journalTitle, journalDate, status) {
            document.getElementById('viewStudentNumber').textContent = studentNumber;
            document.getElementById('viewStudentName').textContent = studentName;
            document.getElementById('viewJournalTitle').textContent = journalTitle;
            document.getElementById('viewJournalDate').textContent = journalDate;
            document.getElementById('viewStatus').textContent = status;

            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
        }

        function copyTable() {
            const table = document.getElementById('datatablesSimple');
            const range = document.createRange();
            const sel = window.getSelection();
            sel.removeAllRanges();
            const rows = table.querySelectorAll('tbody tr');
            const copiedRows = [];

            rows.forEach(row => {
                const cells = row.querySelectorAll('td:not(:last-child)');
                const rowText = Array.from(cells).map(cell => cell.innerText.trim()).join('\t');
                copiedRows.push(rowText);
            });

            const tableText = copiedRows.join('\n');

            const tempTextArea = document.createElement('textarea');
            tempTextArea.value = tableText;
            document.body.appendChild(tempTextArea);
            tempTextArea.select();
            document.execCommand('copy');
            document.body.removeChild(tempTextArea);
            alert('Table copied to clipboard');
        }
        
        function exportTableToCSV(filename) {
            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tbody tr');
            const csv = [];
            rows.forEach(row => {
                const cols = row.querySelectorAll('td:not(:last-child)');
                const rowCsv = Array.from(cols).map(col => col.innerText.trim()).join(',');
                csv.push(rowCsv);
            });
            downloadCSV(csv.join('\n'), filename);
        }
        
        function downloadCSV(csv, filename) {
            const csvFile = new Blob([csv], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function exportTableToExcel(filename) {
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
            table.querySelector('thead tr').deleteCell(actionsColumnIndex);
            table.querySelectorAll('tbody tr').forEach(row => {
                row.deleteCell(actionsColumnIndex);
            });
            const currentDate = new Date().toLocaleString();
            const win = window.open('', '_blank');
            win.document.write('<html><head><title>Journal Entries</title>');
            win.document.write('<style>table {width: 100%; border-collapse: collapse;} th, td {padding: 8px; text-align: left; border-bottom: 1px solid #ddd; font-size: 15px;} th {background-color: #f2f2f2;} body {font-family: Arial, sans-serif; font-size: 15px;} .header {margin-bottom: 10px; font-size: 12px; }</style>');
            win.document.write('</head><body>');
            win.document.write('<div class="header">Date and Time Generated: ' + currentDate + '</div>');
            win.document.write('<h2 style="margin-top: 0;">List of Journal Entries</h2>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Student Number</th><th>Student Name</th><th>Journal Title</th><th>Journal Date</th><th>Status</th></tr></thead>');
            win.document.write('<tbody>');
            table.querySelectorAll('tbody tr').forEach(row => {
                let cells = Array.from(row.cells);
                win.document.write('<tr>' + cells.map(cell => `<td>${cell.innerText}</td>`).join('') + '</tr>');
            });
            win.document.write('</tbody>');
            win.document.write('</table>');
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }
    </script>
</body>
</html>
