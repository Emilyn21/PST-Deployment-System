<?php 
include 'includes/auth.php';

$academicYearName = '';
$semesterType = '';

// Fetch the Cooperating Teacher ID based on the logged-in user's ID
$sqlCTID = "SELECT id FROM tbl_cooperating_teacher WHERE user_id = ?";
$stmt = $conn->prepare($sqlCTID);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ct = $result->fetch_assoc();
$ct_id = $ct['id'];

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

    // Modify the SQL query to fetch only pre-service teachers assigned to this cooperating teacher
    $stmt = $conn->prepare("SELECT 
        tpst.student_number,
        CONCAT(tu.last_name, ', ', tu.first_name, 
        CASE 
            WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name)
            ELSE ''
        END) AS student_name,
        tu.email, 
        tay.academic_year_name, 
        CONCAT(
            COALESCE(tp.program_abbreviation, 'N/A'),
            CASE 
                WHEN tm.major_abbreviation IS NOT NULL THEN CONCAT('-', tm.major_abbreviation)
                ELSE ''
            END
        ) AS program_major, 
        tp.id AS program_id, 
        tm.major_name AS major, 
        tm.id AS major_id,
        CASE 
            WHEN COALESCE(tu.street, tu.barangay, tu.city_municipality, tu.province) IS NULL 
            THEN ''
            ELSE TRIM(CONCAT(
                COALESCE(tu.street, ''), 
                CASE WHEN tu.street IS NOT NULL AND tu.barangay IS NOT NULL THEN ', ' ELSE ' ' END, 
                COALESCE(tu.barangay, ''), 
                CASE WHEN tu.barangay IS NOT NULL AND tu.city_municipality IS NOT NULL THEN ', ' ELSE ' ' END, 
                COALESCE(tu.city_municipality, ''), 
                CASE WHEN tu.city_municipality IS NOT NULL AND tu.province IS NOT NULL THEN ', ' ELSE ' ' END, 
                COALESCE(tu.province, '')
            ))
        END AS address,
        tcs.school_name,
        TRIM(
                CONCAT(
                    tadvu.first_name, 
                    CASE 
                        WHEN tadvu.middle_name IS NOT NULL AND tadvu.middle_name != '' THEN CONCAT(' ', tadvu.middle_name) 
                        ELSE '' 
                    END, 
                    ' ', 
                    tadvu.last_name
                )
            ) AS adviser_name
    FROM 
        tbl_placement tpl
    JOIN 
        tbl_pre_service_teacher tpst ON tpl.pre_service_teacher_id = tpst.id
    JOIN 
        tbl_user tu ON tpst.user_id = tu.id
    JOIN 
        tbl_school tcs ON tpl.school_id = tcs.id
    JOIN
        tbl_semester ts ON tpst.semester_id = ts.id
    JOIN
        tbl_academic_year tay ON ts.academic_year_id = tay.id
    LEFT JOIN 
        tbl_adviser_assignment aa ON aa.placement_id = tpl.id
    LEFT JOIN 
        tbl_adviser ta ON ta.id = aa.adviser_id
    LEFT JOIN 
        tbl_user tadvu ON ta.user_id = tadvu.id
    LEFT JOIN 
        tbl_cooperating_teacher_assignment cta ON tpl.id = cta.placement_id
    LEFT JOIN 
        tbl_cooperating_teacher tct ON tct.id = cta.cooperating_teacher_id
    LEFT JOIN 
        tbl_user tctu ON tctu.id = tct.user_id
    JOIN 
        tbl_program tp ON tpst.program_id = tp.id
    LEFT JOIN 
        tbl_major tm ON tpst.major_id = tm.id
    WHERE 
        tpl.isDeleted = 0
        AND tpl.status = 'approved'
        AND tpst.placement_status = 'placed'
        AND tu.isDeleted = 0
        AND cta.cooperating_teacher_id = ?");

    $stmt->bind_param("i", $ct_id);
    $stmt->execute();
    $query = $stmt->get_result();
} else {

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
    <title>Assigned Pre-Service Teachers - Cooperating Teacher</title>
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
    <div id="layoutSidenav" role="navigation">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main role="main">
                <div class="container-fluid px-4">
                    <h1 class="mt-5 h3" id="main-heading">Assigned Pre-Service Teachers</h1>
                    <ol class="breadcrumb mb_4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Assigned Pre-Service Teachers</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0" role="note">
                                You can view the pre-service teachers assigned to you and their assigned adviser below.
                            </p>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header" role="banner">
                            <i class="fas fa-table me-1"></i>
                            Pre-Service Teachers
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
                            <table id="datatablesSimple" class="table table-bordered" role="table" aria-label="Pre-Service Teachers Table">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Pre-Service Teacher</th>
                                        <th scope="col" role="columnheader">Email Address</th>
                                        <th scope="col" role="columnheader">Program - Major</th>
                                        <th scope="col" role="columnheader">Adviser</th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <?php while ($row = $query->fetch_assoc()): ?>
                                    <tr role="row">
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['program_major']); ?></td>
                                        <td role="cell">
                                            <?php 
                                            if ($row['adviser_name'] === 'Pending' || is_null($row['adviser_name'])) {
                                                echo '<span class="badge bg-warning">Pending</span>';
                                            } else {
                                                echo htmlspecialchars($row['adviser_name'], ENT_QUOTES, 'UTF-8');
                                            }
                                            ?>
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
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                const dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                    labels: {
                        noRows: "No pre-service teachers have been assigned to you yet."
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
            let filename = 'List of Assigned Pre-Service Teachers';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.csv`;
            } else {
                filename += '.csv';
            }
            const table = document.getElementById('datatablesSimple');
            if (!table) return;

            let csv = [];

            // Get column headers (excluding the "Actions" column)
            const headers = ["Pre-Service Teacher", "Email Address", "Program-Major", "Adviser"];
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
            let filename = 'List of Assigned Pre-Service Teachers';

            if (semesterType && academicYearName) {
                filename += ` for ${semesterType} Semester A.Y. ${academicYearName}.xlsx`;
            } else {
                filename += '.xlsx';
            }

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
            win.document.write('<html><head><title>List of Assigned Pre-Service Teachers</title>');
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
            // Modify the title to include semester and academic year, or just "List of Pre-Service Teachers" if not available
            let title = 'List of Assigned Pre-Service Teachers';
            if (semester && academicYear) {
                title += ` for ${semester} Semester A.Y. ${academicYear}`;
            } else if (semester) {
                title += ` for ${semester} Semester`;
            } else if (academicYear) {
                title += ` A.Y. ${academicYear}`;
            }

            win.document.write(`<div class="header-container"><p class="title-text">${title}</p></div>`);
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Pre-Service Teacher Name</th><th>Email Address</th><th>Program - Major</th><th>Adviser</th></tr></thead><tbody>');

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