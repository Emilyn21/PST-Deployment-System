<?php 
include 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Schedule Visit - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }    /* Custom error message styling */
    .invalid-date {
        border: 2px solid red;
    }

    .error-message {
        color: red;
        font-size: 0.875rem;
        margin-top: 5px;
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
                    <h1 class="mt-5 h3" id="main-heading">Schedule Visit</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Schedule Visit</li>
                    </ol>
                    <section class="row" role="region" aria-labelledby="section-heading">
                        <h2 id="section-heading" class="visually-hidden">Content Section</h2>
                        <article class="col-md-12" role="article">
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <p class="mb-0" role="note">Fill in visit details. Visits can only be sent to schools with active school administrators. If a school is missing from the list, it may not have an active administrator. You may need to <a href="add-school-admin.php"> add school admininstrator</a> for the school or <a href="manage-school-admin.php">update their status</a> in the school administrator management section.</p>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="card mb-4" role="complementary">
                                <div class="card-body">
                                    <form id="scheduleVisitForm" action="functions/process-schedule-visit.php" method="POST" class="main-content" role="form">
                                        <!-- Visit Information Section -->
                                        <fieldset role="region" aria-labelledby="legend-visit-info" class="p-3 border rounded">
                                            <legend id="legend-visit-info" class="w-auto">Visit Information</legend>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="school" class="form-label">Name of School:</label>
                                                    <select id="school" name="school" required class="form-select" role="combobox">
                                                        <option value="" disabled selected>Select school</option>
                                                        <?php
                                                        $schoolQuery = "
                                                            SELECT s.id, s.school_name
                                                            FROM tbl_school s
                                                            JOIN tbl_school_admin sa ON s.id = sa.school_id
                                                            JOIN tbl_user u ON sa.user_id = u.id
                                                            WHERE s.status = 'active' 
                                                              AND s.isDeleted = 0
                                                              AND u.account_status = 'active'
                                                              AND u.isDeleted = 0
                                                            GROUP BY s.id, s.school_name
                                                        ";

                                                        $schoolResult = $conn->query($schoolQuery);
                                                        if ($schoolResult && $schoolResult->num_rows > 0) {
                                                            // Populate school options
                                                            while ($school = $schoolResult->fetch_assoc()) {
                                                                echo "<option value=\"{$school['id']}\">{$school['school_name']}</option>";
                                                            }
                                                        } else {
                                                            echo "<option value=\"\" disabled>No active schools available</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="visit-type" class="form-label">Type:</label>
                                                    <select id="visit-type" name="visit_type" required class="form-select" role="combobox">
                                                        <option value="" disabled selected>Select type</option>
                                                        <?php
                                                        // Fetch active visit types
                                                        $visit_types = $conn->query("SELECT id, type_name FROM tbl_visit_types");

                                                        if ($visit_types && $visit_types->num_rows > 0):
                                                            while ($type = $visit_types->fetch_assoc()):
                                                        ?>
                                                                <option value="<?= htmlspecialchars($type['id']); ?>"><?= htmlspecialchars($type['type_name']); ?></option>
                                                        <?php
                                                            endwhile;
                                                        else:
                                                        ?>
                                                            <option value="">No types found</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="date" class="form-label">Date:</label>
                                                    <input type="date" id="date" name="date" required class="form-control" role="textbox" aria-describedby="dateValid">
                                                    <div id="dateValid" class="invalid-feedback">
                                                        Please select a future date for the visit.
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="time-slot" class="form-label">Time Slot:</label>
                                                    <input type="time" id="time-slot" name="time_slot" required class="form-control" role="textbox" aria-describedby="timeValid">
                                                    <div id="timeValid" class="invalid-feedback">
                                                        Please select a valid time for the visit.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="title" class="form-label">Title:</label>
                                                    <input type="text" id="title" name="title" placeholder="Enter visit title" class="form-control" required role="textbox">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="visit-details" class="form-label">Details:</label>
                                                    <textarea id="visit-details" name="visit_details" placeholder="Enter visit details" class="form-control" required role="textbox"></textarea>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                                <!-- Submit Button -->
                                <div class="card-footer">
                                    <button form="scheduleVisitForm" type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="manage-visit.php" class="btn btn-primary">View List</a>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const dateField = document.getElementById('date');
            const timeField = document.getElementById('time-slot');
            const form = document.getElementById('scheduleVisitForm');

            dateField.addEventListener('input', function () {
                validateFutureDateTime(dateField, timeField);
            });

            timeField.addEventListener('input', function () {
                validateFutureDateTime(dateField, timeField);
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const isDateTimeValid = validateFutureDateTime(dateField, timeField);

                if (isDateTimeValid) {
                    const formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => showModal(data))
                    .catch(error => showModal({ status: 'error', message: 'An unknown error occurred. Please try again.' }));
                }
            });

            function validateFutureDateTime(dateInput, timeInput) {
                const dateValue = dateInput.value;
                const timeValue = timeInput.value;
                
                if (!dateValue || !timeValue) {
                    resetValidation(dateInput);
                    resetValidation(timeInput);
                    return false;
                }

                const selectedDateTime = new Date(`${dateValue}T${timeValue}`);
                const now = new Date();

                const isValid = selectedDateTime > now;
                resetValidation(dateInput);
                resetValidation(timeInput);

                if (!isValid) {
                    dateInput.classList.add('is-invalid');
                    timeInput.classList.add('is-invalid');
                }

                return isValid;
            }

            function resetValidation(inputField) {
                inputField.classList.remove('is-invalid');
            }

            function showModal(response) {
                const modalId = response.status === 'success' ? 'successModal' : 'errorModal';
                const modal = new bootstrap.Modal(document.getElementById(modalId));
                modal.show();

                document.getElementById(modalId).querySelector('.modal-body').textContent = response.message;

                if (response.status === 'success') {
                    document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
                        resetForm();
                        location.reload();
                    });
                }
            }

            function resetForm() {
                form.reset();
                resetValidation(dateField);
                resetValidation(timeField);
            }
        });
    </script>
</body>
</html>
