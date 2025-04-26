<?php
include 'includes/auth.php';

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
$semesterRow = $semesterResult->fetch_assoc();
$activeSemesterId = $semesterRow['id'] ?? null;
$semesterType = $semesterRow['type'] ?? null;

$errorMessage = null; // Variable to hold the error message

if (!$activeSemesterId) {
    $errorMessage = "No active semester found. Please set an active semester before adding pre-service teachers.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'checkExists') {
        $field = $_POST['field'];
        $value = $_POST['value'];

        $table = '';
        $column = '';

        if ($field === 'email') {
            $table = 'tbl_user';
            $column = 'email';
        } elseif ($field === 'studentNumber') {
            $table = 'tpst';
            $column = 'student_number';
        }

        if ($table && $column) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
            $stmt->execute([$value]);
            $exists = $stmt->fetchColumn() > 0;

            echo json_encode(['exists' => $exists]);
            exit;
        }

        echo json_encode(['exists' => false]);
        exit;
    }

    // Your existing form submission logic here
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add Pre-Service Teachers - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
                    <h1 class="mt-5 h3" id="main-heading">Add Pre-Service Teachers</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Add Pre-Service Teachers</li>
                    </ol>
                    <section class="row" role="region" aria-labelledby="section-heading">
                        <h2 id="section-heading" class="visually-hidden">Content Section</h2>
                        <article class="col-md-12" role="article">
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <p class="mb-0" role="note">Fill in pre-service teacher details. Make sure to fill in the student number and email address correctly.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <form id="preServiceTeacherForm" action="functions/process-add-pre-service-teacher.php" method="POST" class="main-content" role="form">
                                        <!-- Personal Information Section -->
                                        <fieldset role="region" aria-labelledby="legend-personal-info" class="p-3 border rounded">
                                            <legend id="legend-personal-info" class="w-auto">Personal Information</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="first-name" class="form-label">First Name:</label>
                                                    <input type="text" id="first-name" name="first_name" required placeholder="Enter first name" class="form-control" role="textbox">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="middle-name" class="form-label">Middle Name:</label>
                                                    <input type="text" id="middle-name" name="middle_name" placeholder="Enter middle name" class="form-control" role="textbox">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="last-name" class="form-label">Last Name:</label>
                                                    <input type="text" id="last-name" name="last_name" required placeholder="Enter last name" class="form-control" role="textbox">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="email" class="form-label">Email Address:</label>
                                                    <input type="email" id="email" name="email" required placeholder="Enter email address" class="form-control" role="textbox">
                                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                                </div>
                                            </div>
                                        </fieldset>

                                        <!-- Academic Information Section -->
                                        <fieldset role="region" aria-labelledby="legend-academic-info" class="p-3 border rounded mt-4">
                                            <legend id="legend-academic-info" class="w-auto">Academic Information</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="studentNumber" class="form-label">Student Number:</label>
                                                    <input type="number" id="studentNumber" name="studentNumber" required placeholder="Enter student number" class="form-control" role="spinbutton">
                                                    <div class="invalid-feedback">
                                                        Student number should be exactly 9 digits.
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="program" class="form-label">Program:</label>
                                                    <select id="program" name="program" class="form-select" role="combobox" onchange="updateMajors(this.value, '');" required>
                                                        <option value="" disabled selected>Select program</option>
                                                        <?php
                                                        $query = "SELECT id, program_name, withMajor FROM tbl_program WHERE status = 'active' AND isDeleted = 0";
                                                        $stmt = $conn->prepare($query);
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();

                                                        if (!$result) {
                                                            echo '<option value="">Error fetching programs</option>';
                                                        } else {
                                                            while ($row = mysqli_fetch_assoc($result)) {
                                                                echo '<option value="' . htmlspecialchars($row['id']) . '" data-with-major="' . htmlspecialchars($row['withMajor']) . '">' . htmlspecialchars($row['program_name']) . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4" id="major-container" hidden>
                                                    <label for="major" class="form-label">Major:</label>
                                                    <select id="major" name="major" class="form-select" role="combobox">
                                                        <option value="" disabled selected>Select major</option>
                                                        <!-- Major options populated by JavaScript -->
                                                    </select>
                                                </div>
                                            </div>
                                            <input type="hidden" id="semesterId" name="semesterId" value="<?php echo htmlspecialchars($activeSemesterId); ?>">
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button -->
                                <div class="card-footer">
                                    <button type="submit" form="preServiceTeacherForm" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                                </div>
                            </div>
                        </article>
                    </section>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" role="dialog" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel"><i class="fas fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-pre-service-teacher.php" class="btn btn-primary" role="button">View List</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" role="dialog" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel"><i class="fas fa-times-circle"></i> Error</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const errorMessage = <?php echo json_encode($errorMessage); ?>;

            if (errorMessage) {
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                const errorModalBody = document.querySelector('#errorModal .modal-body');
                errorModalBody.textContent = errorMessage;
                errorModal.show();
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('preServiceTeacherForm');
            const studentNumberInput = document.getElementById('studentNumber');
            const emailInput = document.getElementById('email');

            // Function to validate student number
            function validateStudentNumber(input) {
                const value = input.value;
                const isValid = /^\d{9}$/.test(value);  // Validates if the student number is exactly 9 digits

                if (isValid) {
                    input.classList.remove('is-invalid');
                } else {
                    input.classList.add('is-invalid');
                }

                return isValid;
            }

            // Function to validate email
            function validateEmail(input) {
                const value = input.value;
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);  // Validates email format

                if (isValid) {
                    input.classList.remove('is-invalid');
                } else {
                    input.classList.add('is-invalid');
                }

                return isValid;
            }

            studentNumberInput.addEventListener('input', () => {
                validateStudentNumber(studentNumberInput);
            });

            emailInput.addEventListener('input', () => {
                validateEmail(emailInput);
            });

            form.addEventListener('submit', function (e) {
                const isValidStudentNumber = validateStudentNumber(studentNumberInput);
                const isValidEmail = validateEmail(emailInput);

                if (!isValidStudentNumber || !isValidEmail) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();

                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Raw Response:', data);

                    if (data.status === 'error') {
                        if (data.errors) {
                            data.errors.forEach(error => {
                                if (error.field === 'studentNumber') {
                                    studentNumberInput.classList.add('is-invalid');
                                    const feedback = studentNumberInput.nextElementSibling;
                                    if (feedback) {
                                        feedback.textContent = error.message;
                                    }
                                } else if (error.field === 'email') {
                                    emailInput.classList.add('is-invalid');
                                    const feedback = emailInput.nextElementSibling;
                                    if (feedback) {
                                        feedback.textContent = error.message;
                                    }
                                }
                            });
                        }
                    } else {
                        showModal(data);
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    showModal({ status: 'error', message: 'An unknown error occurred. Please try again.' });
                });
            });

            function showModal(response) {
                const modalId = response.status === 'success' ? 'successModal' : 'errorModal';
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();

                document.getElementById(modalId).querySelector('.modal-body').textContent = response.message;

                if (response.status === 'success') {

                    document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
                        resetFormAndDropdowns();
                        location.reload();
                    });
                }
            }

            function resetFormAndDropdowns() {
                form.reset();

                // Reset major dropdown
                const majorSelect = document.getElementById('major');
                majorSelect.innerHTML = '<option value="" disabled selected>Select program first</option>';
                majorSelect.removeAttribute('required');
            }
        });

        const majorsData = <?php
            $majorsQuery = "SELECT id, major_name, program_id FROM tbl_major WHERE isDeleted = 0 AND status = 'active'";
            $majorsResult = mysqli_query($conn, $majorsQuery);
            $majors = [];
            while ($row = mysqli_fetch_assoc($majorsResult)) {
                $majors[] = $row;
            }
            echo json_encode($majors);
        ?>;

        window.updateMajors = function(programId, selectedMajorId) {
            const majorSelect = document.getElementById('major');
            const majorContainer = document.getElementById('major-container');
            const programSelect = document.getElementById('program');
            const selectedProgramOption = programSelect.querySelector(`option[value="${programId}"]`);

            majorSelect.innerHTML = '';
            majorSelect.innerHTML += '<option value="null" disabled selected>Select major</option>';

            let foundMajor = false;
            let hasMajor = false;

            // Show or hide the major field based on program's withMajor value
            if (selectedProgramOption) {
                const withMajor = selectedProgramOption.getAttribute('data-with-major') === '1';

                if (withMajor) {
                    majorContainer.removeAttribute('hidden'); // Show the major dropdown
                } else {
                    majorContainer.setAttribute('hidden', 'true'); // Hide the major dropdown
                }
            }

            majorsData.forEach(major => {
                if (major.program_id == programId) {
                    foundMajor = true;
                    hasMajor = true;
                    const selected = major.id == selectedMajorId ? 'selected' : '';
                    majorSelect.innerHTML += `<option value="${major.id}" ${selected}>${major.major_name}</option>`;
                }
            });

            if (!foundMajor) {
                majorSelect.innerHTML += `<option value="null" selected>Not available</option>`;
            }

            if (hasMajor) {
                majorSelect.setAttribute('required', 'required');
            } else {
                majorSelect.removeAttribute('required');
            }
        };

    </script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
