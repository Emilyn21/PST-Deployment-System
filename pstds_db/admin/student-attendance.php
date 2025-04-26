<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Dashboard - Browse Attendance</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        @media (max-width: 576px) {
            .flex-wrap-nowrap {
                flex-wrap: wrap !important;
            }
        }
        .form-group {
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
                    <h1 class="mt-4">Student Attendance</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Student Attendance</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                Browse attendance by specific pre-service teacher using date ranges.
                            </p>
                            <div class="row align-items-center mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="studentSelect" class="form-label">Select Student:</label>
                                        <select id="studentSelect" class="form-control" style="max-width: 300px;">
                                            <option value="cedric">Cedric Kelly</option>
                                            <option value="garrett">Garrett Winters</option>
                                            <option value="tiger">Tiger Nixon</option>
                                            <option value="ashton">Ashton Cox</option>
                                            <option value="airi">Airi Satou</option>
                                            <option value="jessie">Jessie Ray</option>
                                            <option value="mary">Mary Ray</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center mt-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="startDatePicker" class="form-label">Start Date:</label>
                                        <input type="date" id="startDatePicker" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="endDatePicker" class="form-label">End Date:</label>
                                        <input type="date" id="endDatePicker" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center mt-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <button id="generateReportBtn" class="btn btn-primary">Generate Report</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Attendance Records
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV('attendance.csv')" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel('attendance.xlsx')" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Program - Major</th>
                                        <th>School</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Cedric Kelly</td>
                                        <td>BSE</td>
                                        <td>Edinburgh</td>
                                        <td>2023-07-04 08:15 AM</td>
                                        <td>2023-07-04 04:15 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Cedric Kelly</td>
                                        <td>BSE</td>
                                        <td>Edinburgh</td>
                                        <td>2023-07-05 08:15 AM</td>
                                        <td>2023-07-05 04:15 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Garrett Winters</td>
                                        <td>BECED</td>
                                        <td>Tokyo</td>
                                        <td>2023-07-05 08:30 AM</td>
                                        <td>2023-07-05 04:30 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Cedric Kelly</td>
                                        <td>BSE</td>
                                        <td>Edinburgh</td>
                                        <td>2023-07-06 08:15 AM</td>
                                        <td>2023-07-06 04:15 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Tiger Nixon</td>
                                        <td>BEED</td>
                                        <td>Edinburgh</td>
                                        <td>2023-07-06 08:00 AM</td>
                                        <td>2023-07-06 04:00 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Ashton Cox</td>
                                        <td>BSNED</td>
                                        <td>San Francisco</td>
                                        <td>2023-07-06 07:45 AM</td>
                                        <td>2023-07-06 03:45 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Airi Satou</td>
                                        <td>BTLED</td>
                                        <td>Tokyo</td>
                                        <td>2023-07-13 07:30 AM</td>
                                        <td>2023-07-13 03:30 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Jessie Ray</td>
                                        <td>BTLED</td>
                                        <td>Tokyo</td>
                                        <td>2023-08-06 07:30 AM</td>
                                        <td>2023-08-06 03:30 PM</td>
                                    </tr>
                                    <tr>
                                        <td>Mary Ray</td>
                                        <td>BTLED</td>
                                        <td>Edinburgh</td>
                                        <td>2023-08-09 07:30 AM</td>
                                        <td>2023-08-09 03:30 PM</td>
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
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/simple-datatables.min.js"></script>
    <script src="../js/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = new simpleDatatables.DataTable('#datatablesSimple');

            // Set initial dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDatePicker').value = today;
            document.getElementById('endDatePicker').value = today;
        });

        document.getElementById('generateReportBtn').addEventListener('click', function() {
            const selectedStudent = document.getElementById('studentSelect').value;
            const startDate = document.getElementById('startDatePicker').value;
            const endDate = document.getElementById('endDatePicker').value;

            filterTable(selectedStudent, startDate, endDate);
        });

        function filterTable(studentName, startDate, endDate) {
            const table = document.getElementById('datatablesSimple');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[0];
                const timeInCell = rows[i].getElementsByTagName('td')[3];
                const timeInDate = new Date(timeInCell.innerText);
                const isVisible = (
                    nameCell.innerText.toLowerCase().includes(studentName.toLowerCase()) &&
                    timeInDate >= new Date(startDate) &&
                    timeInDate <= new Date(endDate)
                );

                if (isVisible) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

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
    const startDate = document.getElementById('startDatePicker').value;
    const endDate = document.getElementById('endDatePicker').value;
    const selectedStudentName = document.getElementById('studentSelect').options[document.getElementById('studentSelect').selectedIndex].text;

    let formattedDate;
    let reportTitle;
    
    if (startDate === endDate) {
        formattedDate = new Intl.DateTimeFormat('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }).format(new Date(startDate));
        reportTitle = `Daily Attendance Report for ${selectedStudentName} - ${formattedDate}`;
    } else {
        const startDateFormat = new Intl.DateTimeFormat('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }).format(new Date(startDate));

        const endDateFormat = new Intl.DateTimeFormat('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }).format(new Date(endDate));

        reportTitle = `Student Attendance Report for ${selectedStudentName} from ${startDateFormat} to ${endDateFormat}`;
    }

    let currentDate = new Date().toLocaleString();
    let printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Student Attendance Report</title>');
    printWindow.document.write('<style>table {width: 100%; border-collapse: collapse;} th, td {padding: 8px; text-align: left; border-bottom: 1px solid #ddd; font-size: 15px;} th {background-color: #f2f2f2;} body {font-family: Arial, sans-serif; font-size: 15px;} .header {margin-bottom: 10px; font-size: 12px; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="header">Date and Time Generated: ' + currentDate + '</div>');
    printWindow.document.write(`<h2 style="margin-top: 0;">${reportTitle}</h2>`);
    printWindow.document.write('<table>');
    printWindow.document.write('<thead><tr><th>Name</th><th>Program - Major</th><th>School</th><th>Time In</th><th>Time Out</th></tr></thead>');

    const table = document.getElementById("datatablesSimple");
    const rows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
    if (rows.length === 0) {
        printWindow.document.write('<tr><td colspan="5">No records found for the selected date range.</td></tr>');
    } else {
        rows.forEach((row) => {
            const timeInCell = row.cells[3].innerText.trim();
            const timeInDate = timeInCell.split(' ')[0];
            const rowDate = new Date(timeInDate);
            if (rowDate >= new Date(startDate) && rowDate <= new Date(endDate)) {
                let cells = Array.from(row.cells);
                printWindow.document.write('<tr>' + cells.map(cell => `<td>${cell.innerText}</td>`).join('') + '</tr>');
            }
        });
    }
    printWindow.document.write('</table>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

    </script>
</body>

</html>
