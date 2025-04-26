<?php
include 'connect.php'; // Ensure database connection

if (!isset($_GET['token'])) {
    echo "<script>alert('Invalid password reset link.'); window.location.href='password-recovery.php';</script>";
    exit();
}

$token = $_GET['token'];

// Verify token in database
$stmt = $conn->prepare("SELECT email FROM tbl_password_reset WHERE token = ? AND expires_at > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Invalid or expired token.'); window.location.href='password-recovery.php';</script>";
    exit();
}
$email = $result->fetch_assoc()['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Password Recovery</title>
    <link href="css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="css/poppins.css" rel="stylesheet">
    <script src="js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #6c757d;
        }
        .card-header {
            background-color: #f8f9fa;
        }
        .form-instruction {
            margin-bottom: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        .input-group .btn {
            border-left: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/topnav.php'; ?>

    <div id="layoutAuthentication">
        <main class="pb-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5">
                        <div class="card shadow-lg border-0 rounded-lg mt-5">
                            <div class="card-header">
                                <h3 class="text-center font-weight-light my-4">Reset Password</h3>
                            </div>
                            <div class="card-body">
                                <form id="updatePasswordForm" method="post" action="functions/process-password-reset-link.php">
                                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <input class="form-control" type="password" name="new_password" id="new_password" placeholder="New Password" required />
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <input class="form-control" type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required />
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                                    </div>
                                </form>

                                <div class="text-center mt-3">
                                    <a class="small" href="login.php">Go back to Login</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Success</h5>
                </div>
                <div class="modal-body">
                    Password has been reset successfully!
                </div>
                <div class="modal-footer">
                    <a href="login.php" class="btn btn-success">Go to Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Error</h5>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        document.getElementById('updatePasswordForm').addEventListener('submit', function (event) {
            if (!validatePassword()) {
                event.preventDefault(); // Prevent submission if validation fails
                return;
            }
            
            event.preventDefault(); // Stop default form submission
            resetPassword(); // Call AJAX function to handle password reset
        });


        function validatePassword() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // Find the closest invalid-feedback div inside the same input-group
            const confirmPasswordFeedback = confirmPasswordInput.parentElement.querySelector('.invalid-feedback');

            const newPasswordValue = newPasswordInput.value;
            const confirmPasswordValue = confirmPasswordInput.value;

            if (newPasswordValue !== confirmPasswordValue) {
                confirmPasswordInput.classList.add('is-invalid');
                confirmPasswordFeedback.textContent = "Passwords do not match.";
                return false;
            } else {
                confirmPasswordInput.classList.remove('is-invalid');
                confirmPasswordFeedback.textContent = "";
            }
            return true;
        }

        document.getElementById('confirm_password').addEventListener('input', validatePassword);
        document.getElementById('new_password').addEventListener('input', validatePassword);

        document.querySelectorAll(".toggle-password").forEach(button => {
            button.addEventListener("click", function() {
                let target = document.getElementById(this.getAttribute("data-target"));
                if (target.type === "password") {
                    target.type = "text";
                    this.innerHTML = '<i class="fa fa-eye-slash"></i>';
                } else {
                    target.type = "password";
                    this.innerHTML = '<i class="fa fa-eye"></i>';
                }
            });
        });

        function resetPassword() {
            let formData = new FormData(document.getElementById('updatePasswordForm'));

            fetch("functions/process-password-reset-link.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    new bootstrap.Modal(document.getElementById("successModal")).show();
                } else {
                    showErrorModal(data.message);
                }
            })
            .catch(() => {
                showErrorModal("An unexpected error occurred. Please try again.");
            });
        }
    </script>
</body>
</html>
