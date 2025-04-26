<?php 
include 'includes/auth.php';

// Fetch the adviser ID based on the logged-in user's ID
$sqlAdviserID = "SELECT id FROM tbl_adviser WHERE user_id = ?";
$stmt = $conn->prepare($sqlAdviserID);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$adviser = $result->fetch_assoc();
$adviser_id = $adviser['id'];

// Initialize filter variables
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Modify the SQL query to fetch only pre-service teachers assigned to this adviser
$sql = "SELECT 
    tpst.student_number,
    CONCAT(tu.last_name, ', ', tu.first_name, 
    CASE 
        WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
        ELSE ''
    END) AS student_name,
    CONCAT(
        COALESCE(tp.program_abbreviation, 'N/A'),
        CASE 
            WHEN tm.major_abbreviation IS NOT NULL THEN CONCAT('-', tm.major_abbreviation)
            ELSE ''
        END
    ) AS program_major,
    ts.school_name, 
    DATE(tatt.time_in) AS date,
    DATE_FORMAT(tatt.time_in, '%H:%i:%s') AS time_in,
    DATE_FORMAT(tatt.time_out, '%H:%i:%s') AS time_out
FROM 
    tbl_placement tpl
JOIN 
    tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
JOIN 
    tbl_attendance tatt ON tpst.id = tatt.pre_service_teacher_id
JOIN 
    tbl_user tu ON tpst.user_id = tu.id
JOIN 
    tbl_school ts ON tpl.school_id = ts.id
LEFT JOIN 
    tbl_adviser_assignment taa ON taa.placement_id = tpl.id
LEFT JOIN 
    tbl_adviser tadv ON tadv.id = taa.adviser_id
LEFT JOIN 
    tbl_user tadvu ON tadv.user_id = tadvu.id
JOIN 
    tbl_program tp ON tpst.program_id = tp.id
LEFT JOIN 
    tbl_major tm ON tpst.major_id = tm.id
WHERE 
    tpl.isDeleted = 0
    AND taa.adviser_id = ?";

// Apply date filter if specified
if ($startDate && $endDate) {
    $sql .= " AND DATE(tatt.time_in) BETWEEN ? AND ?";
}

$stmt = $conn->prepare($sql);

if ($startDate && $endDate) {
    $stmt->bind_param("iss", $adviser_id, $startDate, $endDate);
} else {
    $stmt->bind_param("i", $adviser_id);
}

$stmt->execute();
$query = $stmt->get_result();

// Check if there are any records found
$noRecordsFound = $query->num_rows === 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />

    <title>Attendance Records - Adviser</title>

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
                    <h1 class="mt-5 h3" id="main-heading">Attendance Records</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Attendance Records</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                Generate attendance records for pre-service teachers using date picker.
                            </p>

                            <div class="row align-items-center mt-3" role="group" aria-labelledby="dateRangeSection">
                                <h3 id="dateRangeSection" class="visually-hidden">Date Range Selection</h3>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="startDatePicker" class="form-label">Start Date:</label>
                                        <input type="date" id="startDatePicker" class="form-control" style="max-width: 200px;" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>" aria-required="true">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="endDatePicker" class="form-label">End Date:</label>
                                        <input type="date" id="endDatePicker" class="form-control" style="max-width: 200px;" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?>" aria-required="true">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <form method="get" action="">
                                        <button type="submit" id="generateReportBtn" class="btn btn-primary ms-auto report-button-custom" role="button" aria-label="Generate attendance report">Filter</button>
                                        <input type="hidden" name="start_date" id="startDate" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="end_date" id="endDate" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?>">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Attendance Records
                        </div>

                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary" role="button">Copy</button>
                                    <button onclick="exportTableToCSV('attendance.csv')" class="btn btn-secondary" role="button">CSV</button>
                                    <button onclick="exportTableToExcel('attendance.xlsx')" class="btn btn-secondary" role="button">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary" role="button">Print</button>
                                </div>
                            </div>

                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Attendance Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Date</th>
                                        <th scope="col" role="columnheader">Student Number</th>
                                        <th scope="col" role="columnheader">Name</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Time In</th>
                                        <th scope="col" role="columnheader">Time Out</th>
                                    </tr>
                                </thead>

                                <tbody role="rowgroup">
                                    <?php if ($noRecordsFound): ?>
                                        <tr role="row">
                                            <td colspan="6" class="text-center no-records-message">No attendance records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($row = $query->fetch_assoc()): ?>
                                            <tr role="row">
                                                <td role="cell"><?= htmlspecialchars(date('M d, Y', strtotime($row['date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-center" role="cell"><?= htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td role="cell"><?= htmlspecialchars($row['time_in'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td role="cell">
                                                    <?= !empty($row['time_out']) ? htmlspecialchars($row['time_out'], ENT_QUOTES, 'UTF-8') : 'No Time Out Record'; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script src="../js/xlsx.full.min.js"></script>
    <script>
        const startDatePicker = document.getElementById('startDatePicker');
        const endDatePicker = document.getElementById('endDatePicker');
        const form = document.querySelector('form');

        startDatePicker.addEventListener('change', function() {
            document.getElementById('startDate').value = this.value;
        });
        endDatePicker.addEventListener('change', function() {
            document.getElementById('endDate').value = this.value;
        });

        function copyTable() {
            const table = document.getElementById('datatablesSimple');
            let range, sel;
            if (document.createRange && window.getSelection) {
                range = document.createRange();
                sel = window.getSelection();
                sel.removeAllRanges();
                try {
                    range.selectNodeContents(table);
                    sel.addRange(range);
                } catch (e) {
                    range.selectNode(table);
                    sel.addRange(range);
                }
                document.execCommand('copy');
                alert('Table copied to clipboard');
            }
        }

        function exportTableToCSV(filename) {
            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tr');
            const csv = [];
            for (const row of rows) {
                const cols = row.querySelectorAll('td, th');
                const rowCsv = [];
                for (const col of cols) {
                    rowCsv.push(col.innerText);
                }
                csv.push(rowCsv.join(','));
            }
            downloadCSV(csv.join('\n'), filename);
        }

        function downloadCSV(csv, filename) {
            const csvFile = new Blob([csv], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }

        function exportTableToExcel(filename) {
            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tbody tr');
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
            const data = [];
            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
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
            var printWindow = window.open('', '_blank');
            var table = document.getElementById('datatablesSimple');
            
            // Create a new HTML structure for the printable version
            var printContent = '<html><head><title>Attendance Report</title>';
            printContent += '<style>';
            printContent += 'body { font-family: Arial, sans-serif; margin: 20px; }';
            printContent += 'table { width: 100%; border-collapse: collapse; }';
            printContent += 'th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }';
            printContent += 'th { background-color: #f2f2f2; }';
            printContent += '</style></head><body>';
            
            // Copy the table's HTML content (filtered rows will be copied)
            printContent += table.outerHTML;
            
            // Close the HTML tags
            printContent += '</body></html>';
            
            // Write the content to the new window
            printWindow.document.open();
            printWindow.document.write(printContent);
            printWindow.document.close();
            
            // Trigger the print dialog
            printWindow.print();
        }
    </script>
</body>
</html>