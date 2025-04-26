<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Evaluation PST - Adviser</title>
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
                    <h1 class="mt-4 h3" id="main-heading">Internship Evaluation</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Internship Evaluation</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                Evaluate the pre-service teacher below.
                            </p>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            PSTs Evaluation
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                   <tr>
                                        <th>Name</th>
                                        <th>Program-Major</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                        <th>Final Grade</th> <!-- Added column for displaying last grade -->
                                    </tr>
                                </thead>
                                <tbody>
                                     <tr>
                                        <td>Cedric Kelly</td>
                                        <td>BSE</td>
                                        <td id="status-1">Not Graded</td>
                                        <td><button class="btn btn-primary" onclick="openGradeModal(1, 'Cedric Kelly', 'BSE')">Grade</button></td>
                                        <td id="lastGrade-1"></td> <!-- Display last grade here -->
                                    </tr>
                                    <tr>
                                        <td>Garrett Winters</td>
                                        <td>BECED</td>
                                        <td id="status-2">Not Graded</td>
                                        <td><button class="btn btn-primary" onclick="openGradeModal(2, 'Garrett Winters', 'BECED')">Grade</button></td>
                                        <td id="lastGrade-2"></td> <!-- Display last grade here -->
                                    </tr>
                                    <!-- Add more students as needed -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
 <!-- Grading Modal -->
    <div class="modal fade" id="gradingModal" tabindex="-1" aria-labelledby="gradingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gradingModalLabel">Grade Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="gradingForm">
                        <input type="hidden" id="studentId">
                        <div class="form-group">
                            <label for="studentNameModal">Name:</label>
                            <input type="text" id="studentNameModal" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label for="programMajorModal">Program-Major:</label>
                            <input type="text" id="programMajorModal" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label for="criteria1">Criteria 1 (Max <span id="criteria1Max"></span>):</label>
                            <input type="number" id="criteria1" name="criteria1" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="criteria2">Criteria 2 (Max <span id="criteria2Max"></span>):</label>
                            <input type="number" id="criteria2" name="criteria2" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="criteria3">Criteria 3 (Max <span id="criteria3Max"></span>):</label>
                            <input type="number" id="criteria3" name="criteria3" class="form-control" required>
                        </div>
                        <button type="button" onclick="submitGrade()" class="btn btn-primary mt-3">Submit Grade</button>
                    </form>
                    <!-- Display total points and grade -->
                    <div class="mt-3">
                        <p>Total Points: <span id="totalPoints">0.00</span></p>
                        <p>Grade: <span id="gradeResult">-</span></p>
                    </div>
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

        // Define a function to set criteria points for all students
        function setCriteriaPoints(criteria1Max, criteria2Max, criteria3Max) {
            const students = [
                { id: 1, name: 'Cedric Kelly', program: 'BSE', criteria1Max, criteria2Max, criteria3Max },
                { id: 2, name: 'Garrett Winters', program: 'BECED', criteria1Max, criteria2Max, criteria3Max }
                // Add more students as needed
            ];

            return students;
        }

        // Usage example: set criteria points for all students
        const students = setCriteriaPoints(20, 30, 50);

        // Load grades from localStorage if available
        const storedGrades = localStorage.getItem('studentGrades');
        // temporary stored in local data
        const studentGrades = storedGrades ? JSON.parse(storedGrades) : {};

        // Display last grade for each student
        students.forEach(student => {
            const lastGrade = studentGrades[student.id];
            document.getElementById(`lastGrade-${student.id}`).innerText = lastGrade ? `Grade: ${lastGrade}` : 'No grades';
        });

        function openGradeModal(studentId, studentName, programMajor) {
            const student = students.find(student => student.id === studentId);
            document.getElementById('studentId').value = studentId;
            document.getElementById('studentNameModal').value = studentName;
            document.getElementById('programMajorModal').value = programMajor;
            document.getElementById('criteria1Max').innerText = student.criteria1Max;
            document.getElementById('criteria2Max').innerText = student.criteria2Max;
            document.getElementById('criteria3Max').innerText = student.criteria3Max;
            document.getElementById('criteria1').value = ''; // Clear previous value
            document.getElementById('criteria2').value = ''; // Clear previous value
            document.getElementById('criteria3').value = ''; // Clear previous value
            document.getElementById('totalPoints').innerText = '0.00'; // Clear previous total points
            document.getElementById('gradeResult').innerText = '-'; // Clear previous grade result
            new bootstrap.Modal(document.getElementById('gradingModal')).show();
        }

        function submitGrade() {
            const studentId = document.getElementById('studentId').value;
            const criteria1 = parseFloat(document.getElementById('criteria1').value) || 0;
            const criteria2 = parseFloat(document.getElementById('criteria2').value) || 0;
            const criteria3 = parseFloat(document.getElementById('criteria3').value) || 0;

            const student = students.find(student => student.id === parseInt(studentId));
            const totalScore = criteria1 + criteria2 + criteria3;

            let grade;
            if (totalScore >= 100) {
                grade = '1.00';
            } else if (totalScore >= 96) {
                grade = '1.25';
            } else if (totalScore >= 93) {
                grade = '1.50';
            } else if (totalScore >= 90) {
                grade = '1.75';
            } else if (totalScore >= 86) {
                grade = '2.00';
            } else if (totalScore >= 83) {
                grade = '2.25';
            } else if (totalScore >= 80) {
                grade = '2.50';
            } else if (totalScore >= 76) {
                grade = '2.75';
            } else if (totalScore >= 73) {
                grade = '3.00';
            } else if (totalScore >= 69) {
                grade = '4.00';
            } else {
                grade = '5.00';
            }

            // Update UI with total points and grade
            document.getElementById('totalPoints').innerText = totalScore.toFixed(2);
            document.getElementById('gradeResult').innerText = grade;

            // Update status to Graded
            document.getElementById(`status-${studentId}`).innerText = 'Graded';
            document.getElementById(`lastGrade-${studentId}`).innerText = `Grade: ${grade}`;

            // Save grade to localStorage
            studentGrades[studentId] = grade;
            localStorage.setItem('studentGrades', JSON.stringify(studentGrades));

            // Close the modal
            document.getElementById('gradingModal').querySelector('.btn-close').click();
        }
    </script>
</body>

</html>
