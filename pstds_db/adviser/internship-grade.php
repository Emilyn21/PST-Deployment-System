<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Internship Grade -Adviser</title>
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

        .btn-action {
            margin-top: 0.2rem;
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
                    <h1 class="mt-4 h3" id="main-heading">Internship Grade</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Internship Grade</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                View and save the pre-service teacher grades below.
                            </p>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            PSTs Grades
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap justify-content-end gap-2">
                                    <button onclick="copyTable()" class="btn btn-secondary">Copy</button>
                                    <button onclick="exportTableToCSV('internship-grade.csv')" class="btn btn-secondary">CSV</button>
                                    <button onclick="exportTableToExcel('internship-grade.xlsx')" class="btn btn-secondary">Excel</button>
                                    <button onclick="printTable()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Program-Major</th>
                                        <th>Criteria 1</th>
                                        <th>Criteria 2</th>
                                        <th>Criteria 3</th>
                                        <th>Final Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Cedric Kelly</td>
                                        <td>BSE</td>
                                        <td>18</td>
                                        <td>28</td>
                                        <td>45</td>
                                        <td>2.75</td>
                                    </tr>
                                    <tr>
                                        <td>Garrett Winters</td>
                                        <td>BECED</td>
                                        <td>20</td>
                                        <td>32</td>
                                        <td>50</td>
                                        <td>2.50</td>
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
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });

        function copyTable() {
            const table = document.getElementById('datatablesSimple');
            const range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            window.getSelection().removeAllRanges();
        }

        function exportTableToCSV(filename) {
            const table = document.getElementById('datatablesSimple');
            const rows = table.querySelectorAll('tbody tr');
            const csv = [];
            rows.forEach(row => {
                const cols = row.querySelectorAll('td');
                const rowArray = Array.from(cols).map(col => col.innerText.trim());
                csv.push(rowArray.join(','));
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
            const wb = XLSX.utils.table_to_book(table, { sheet: 'Sheet JS' });
            XLSX.writeFile(wb, filename);
        }

        function printTable() {
            const table = document.getElementById('datatablesSimple');
            const currentDate = new Date().toLocaleString();
            const win = window.open('', '_blank');
            
            win.document.write('<html><head><title>PST Internship</title>');
            win.document.write('<style>');
            win.document.write('body { font-family: Arial, sans-serif; font-size: 15px; }');
            win.document.write('table { width: 100%; border-collapse: collapse; }');
            win.document.write('th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }');
            win.document.write('th { background-color: #f2f2f2; }');
            win.document.write('.header { margin-bottom: 10px; font-size: 12px; }');
            win.document.write('</style>');
            win.document.write('</head><body>');
            win.document.write('<div class="header">Date and Time Generated: ' + currentDate + '</div>');
            win.document.write('<h2 style="margin-top: 0;">Pre-Service Teacher internship Grades</h2>');
            win.document.write('<table>');
            win.document.write('<thead><tr><th>Name</th><th>Program-Major</th><th>Criteria 1</th><th>Criteria 2</th><th>Criteria 3</th><th>Final Grade</th></tr></thead>');
            win.document.write('<tbody>');
            table.querySelectorAll('tbody tr').forEach(row => {
                const cells = Array.from(row.cells);
                win.document.write('<tr>');
                cells.forEach(cell => {
                    win.document.write('<td>' + cell.innerText + '</td>');
                });
                win.document.write('</tr>');
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
