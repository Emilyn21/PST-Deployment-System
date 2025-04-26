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

$academicYearName = '';
$semesterType = '';

$semesterStmt = $conn->prepare("
    SELECT id, type
    FROM tbl_semester
    WHERE status = 'active' 
      AND isDeleted = 0 
    ORDER BY start_date DESC 
    LIMIT 1
");
$semesterStmt->execute();
$semesterResult = $semesterStmt->get_result();

// Check if an active semester exists
if ($semesterRow = $semesterResult->fetch_assoc()) {
    $activeSemesterId = $semesterRow['id'];
    $semesterType = $semesterRow['type'];

    // Fetch academic year for the active semester
    $stmtAcademicYear = $conn->prepare("
        SELECT tay.academic_year_name
        FROM tbl_academic_year tay
        INNER JOIN tbl_semester ts ON tay.id = ts.academic_year_id
        WHERE ts.id = ?
    ");
    $stmtAcademicYear->bind_param('i', $activeSemesterId);
    $stmtAcademicYear->execute();
    $academicYearResult = $stmtAcademicYear->get_result();

    if ($academicYearRow = $academicYearResult->fetch_assoc()) {
        $academicYearName = $academicYearRow['academic_year_name'];
    }

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
    $result = $stmt->get_result();
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
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-calendar-alt me-2"></i> Attendance Records Filter
                        </div>
                        <div class="card-body">
                            <p class="mb-3 text-muted" role="note">
                                Select a date range to generate attendance records for pre-service teachers.
                            </p>

                            <!-- Date Range Inputs -->
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label for="startDatePicker" class="form-label fw-semibold">Start Date:</label>
                                    <input type="date" id="startDatePicker" class="form-control" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>" aria-required="true">
                                </div>

                                <div class="col-md-4">
                                    <label for="endDatePicker" class="form-label fw-semibold">End Date:</label>
                                    <input type="date" id="endDatePicker" class="form-control" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?>" aria-required="true">
                                </div>

                                <div class="col-md-4 d-flex gap-2">
                                    <form method="get" action="" class="d-flex flex-grow-1">
                                        <input type="hidden" name="start_date" id="startDate" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="end_date" id="endDate" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" id="generateReportBtn" class="btn btn-primary w-100">
                                            <i class="fas fa-filter me-1"></i> Filter
                                        </button>
                                    </form>
                                    <button type="button" id="clearFilterBtn" class="btn btn-outline-secondary w-50">
                                        <i class="fas fa-times-circle me-1"></i> Clear
                                    </button>
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
                            <div id="semesterInfo" style="display: none;"><?php echo htmlspecialchars($semesterType); ?></div>
                            <div id="academicYearInfo" style="display: none;"><?php echo htmlspecialchars($academicYearName); ?></div>
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary" role="button">Copy</button>
                                    <button onclick="exportTableToCSV()" class="btn btn-secondary" role="button">CSV</button>
                                    <button onclick="exportTableToExcel()" class="btn btn-secondary" role="button">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary" role="button">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Attendance Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Date</th>
                                        <th scope="col" role="columnheader">Student Number</th>
                                        <th scope="col" role="columnheader">Pre-Service Teacher</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Time In</th>
                                        <th scope="col" role="columnheader">Time Out</th>
                                    </tr>
                                </thead>

                                <tbody role="rowgroup">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr role="row">
                                            <td role="cell"><?= htmlspecialchars(date('M d, Y', strtotime($row['date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center" role="cell"><?= htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell"><?= htmlspecialchars($row['program_major'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td role="cell">
                                                <?= date("g:i A", strtotime($row['time_in'])); ?>
                                            </td>
                                            <td role="cell">
                                                <?= !empty($row['time_out']) ? date("g:i A", strtotime($row['time_out'])) : 'No Time Out Record'; ?>
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

    <div id="toast" class="toast position-fixed top-50 start-50 translate-middle" style="display: none; z-index: 1050;">
        <div class="toast-body bg-success text-white">
            Table copied to clipboard
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script src="../js/xlsx.full.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            // Initialize DataTable
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No pre-service teachers attendance available."
                    },
                    perPage: 10, // Default entries per page
                    perPageSelect: [10, 25, 50, 100, -1] // Includes 50 and "All"
                });
                // Modify "-1" to show as "All" in dropdown
                setTimeout(() => {
                    document.querySelectorAll(".datatable-dropdown option").forEach(option => {
                        if (option.value == "-1") {
                            option.textContent = "All"; // Change "-1" to "All"
                        }
                    });
                }, 100);
            }

            // Date Pickers
            const startDatePicker = document.getElementById('startDatePicker');
            const endDatePicker = document.getElementById('endDatePicker');

            if (startDatePicker) {
                startDatePicker.addEventListener('change', function () {
                    const startDateInput = document.getElementById('startDate');
                    if (startDateInput) {
                        startDateInput.value = this.value;
                    }
                });
            }

            if (endDatePicker) {
                endDatePicker.addEventListener('change', function () {
                    const endDateInput = document.getElementById('endDate');
                    if (endDateInput) {
                        endDateInput.value = this.value;
                    }
                });
            }
        });

        document.getElementById('clearFilterBtn').addEventListener('click', function() {
            window.location.href = window.location.pathname; // Reload the page without query parameters
        });

        function copyTable() {
            const table = document.getElementById('datatablesSimple');

            if (!table) return;

            const headers = Array.from(table.querySelectorAll('thead th'))
                .map(th => th.innerText.trim())
                .join('\t'); // Join headers with tabs

            const rows = table.querySelectorAll('tbody tr');
            const copiedRows = [];

            rows.forEach(row => {
                const cells = row.querySelectorAll('td'); // Exclude action column
                const rowText = Array.from(cells).map(cell => cell.innerText.trim()).join('\t');
                copiedRows.push(rowText);
            });

            const tableText = headers + '\n' + copiedRows.join('\n'); // Add headers at the top

            // Use Clipboard API to copy text
            navigator.clipboard.writeText(tableText).then(() => {
                // Show the toast notification
                const toast = document.getElementById('toast');
                toast.style.display = 'block';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 2000);  // Hide after 2 seconds
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        function exportTableToCSV() {
            // Get the semester and academic year from hidden divs
            const semesterType = document.getElementById('semesterInfo').innerText.trim().toUpperCase();
            const academicYearName = document.getElementById('academicYearInfo').innerText.trim();

            // Construct the filename dynamically, check for null or empty semesterType and academicYearName
            let filename = 'List of Attendance of Pre-Service Teachers';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Date", "Student Number", "Pre-Service Teacher", "Program-Major", "Time In", "Time Out"];
            csv.push(headers.map(header => `"${header}"`).join(',')); 

            // Get table rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cols = row.querySelectorAll('td'); // Exclude action column
                let rowData = [];

                cols.forEach((col, index) => {
                    let text = col.innerText.trim();

                    // Ensure proper handling of special characters (e.g., Ñ)
                    text = text.normalize("NFC");

                    // Wrap values with commas inside double quotes to prevent CSV misformatting
                    if (text.includes(',') || text.includes('"')) {
                        text = `"${text.replace(/"/g, '""')}"`; // Escape double quotes
                    } else {
                        // Always wrap text in double quotes, even if it doesn't contain special characters
                        text = `"${text}"`;
                    }

                    // Remove multiple spaces
                    if (index === 0) {
                        text = text.replace(/\s+/g, ' ');
                    }

                    rowData.push(text);
                });

                csv.push(rowData.join(','));
            });

            // Call your existing download function
            downloadCSV(csv.join('\n'), filename);
        }

        function downloadCSV(csv, filename) {
            const BOM = '\uFEFF'; // Add UTF-8 BOM to fix special character encoding
            const csvFile = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function exportTableToExcel() {
            // Get the semester and academic year from hidden divs
            const semesterType = document.getElementById('semesterInfo').innerText.trim().toUpperCase();
            const academicYearName = document.getElementById('academicYearInfo').innerText.trim();

            // Construct the filename dynamically, check for null or empty semesterType and academicYearName
            let filename = 'List of Attendance of Pre-Service Teachers';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.xlsx`;
            } else {
                filename += '.xlsx';
            }

            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tbody tr');
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());  // Exclude last header
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
            const table = document.getElementById('datatablesSimple').cloneNode(true);

            const currentDate = new Date().toLocaleString();

            // Fetch semester and academic year from hidden elements
            let semester = document.getElementById('semesterInfo')?.textContent || '';
            const academicYear = document.getElementById('academicYearInfo')?.textContent || '';

            // Capitalize the first letter of the semester value if it's available
            if (semester) {
                semester = semester.charAt(0).toUpperCase() + semester.slice(1).toLowerCase();
            }

            let win = window.open('', '_blank');
            if (!win) {
                alert("Pop-up blocked! Please allow pop-ups for this site.");
                return;
            }
            win.document.write('<html><head><title>Attendance Records</title>');
            win.document.write(`
                <style>
                    @page {
                        margin-top: 5.7mm;   /* 0.38 inches */
                        margin-bottom: 7.9mm; /* 0.31 inches */
                        margin-left: 25.4mm;  /* 1 inch */
                        margin-right: 25.4mm; /* 1 inch */
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 11pt;
                        text-align: center;
                        margin: 0;
                        padding: 0;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                    }
                    th, td { 
                        padding: 6px; 
                        text-align: left; 
                        border-bottom: 1px solid #ddd; 
                        font-size: 11pt;
                        word-wrap: break-word;
                    }
                    th { 
                        background-color: #f2f2f2; 
                    }
                    .header-container { 
                        display: flex; 
                        align-items: center; 
                        justify-content: center; 
                        text-align: center; 
                        margin-bottom: 10px; 
                    }
                    .header-container img { 
                        width: 100px; 
                        height: auto; 
                        margin-right: 15px; 
                    }
                    .text-container { 
                        display: flex; 
                        flex-direction: column; 
                        align-items: center; 
                    }
                    .header-container p { 
                        margin: 0; 
                        line-height: 1.40; 
                    }
                    .gov-text { font-family: "Century Gothic", sans-serif; font-size: 11pt; }
                    .univ-text { font-family: "Bookman Old Style", serif; font-size: 14pt; font-weight: bold; }
                    .campus-text { font-family: "Century Gothic", sans-serif; font-size: 11pt; font-weight: bold; }
                    .location-text { font-family: "Century Gothic", sans-serif; font-size: 10pt; }
                    .college-text { font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; text-align: center; }
                    .title-text { font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; text-align: center; }
                    .generated-text { font-size: 10pt; font-style: italic; margin-top: 15px; }
                </style>
            `);

            win.document.write('</head><body>');
            win.document.write(`
                <div class="header-container" style="margin-bottom: 5px; margin-left: -115px">
                    <img id="cvsuLogo" src="../assets/img/cvsu-logo-header.jpg" alt="CVSU Logo" style="margin-bottom: 30px">
                    <div class="text-container">
                        <p class="gov-text">Republic of the Philippines</p>
                        <p class="univ-text">CAVITE STATE UNIVERSITY</p>
                        <p class="campus-text" style="margin-bottom: 5px">Don Severino de las Alas Campus</p>
                        <p class="location-text">Indang, Cavite</p>
                    </div>
                </div>
            `);
            win.document.write(`
                <div class="header-container" style="margin-bottom: 15px; justify-content: center;">
                    <p class="college-text">COLLEGE OF EDUCATION</p>
                </div>
            `);

            win.document.write('<div class="header-container"><p class="title-text">List of Attendance Records</p></div>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Date</th><th>Student Number</th><th>Pre-Service Teacher</th><th>Program - Major</th><th>Time In</th><th>Time Out</th></tr></thead><tbody>');

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                win.document.write('<tr>');
                cells.forEach(cell => {
                    win.document.write('<td>' + cell.innerText.trim() + '</td>');
                });
                win.document.write('</tr>');
            });

            win.document.write('</tbody></table>');
            win.document.write(`
                <div class="generated-text" style="text-align: right">
                    Date and Time Generated: ${currentDate}
                </div>
            `);

            win.document.write(`
                <script>
                    window.onload = function() {
                        window.print();
                    };
                <\/script>
            `);

            win.document.write('</body></html>');
            win.document.close();
        }
    </script>
</body>
</html>