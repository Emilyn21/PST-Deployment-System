<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Log In</title>
    <link href="css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="css/poppins.css" rel="stylesheet">
    <script src="js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('assets/img/ced-bg.jpg'); /* Set the background image */
            background-size: cover;  /* Make sure the image covers the entire background */
            background-position: center;  /* Center the image */
            background-repeat: no-repeat;  /* Avoid repeating the image */
            height: 100vh;
        }
        /* Add a translucent overlay on top of the background image */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);  /* Black background with 50% opacity */
            z-index: -1;  /* Make sure the overlay is behind the content */
        }
    </style>
</head>
<body>
    <!-- Header include -->
    <?php include 'includes/topnav.php'; ?>
    
    <div class="overlay"></div>
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main class="pb-5">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header"><h3 class="text-center font-weight-light my-4">Login</h3></div>
                                <div class="card-body">
                                <form id="loginForm">
                                    <div class="form-floating mb-3">
                                        <input class="form-control" id="inputEmail" type="email" name="email" placeholder="name@example.com" required />
                                        <label for="inputEmail">Email address</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <input class="form-control" id="inputPassword" type="password" name="password" placeholder="Password" required />
                                        <label for="inputPassword">Password</label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" id="inputRememberPassword" type="checkbox" name="remember" />
                                        <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                                    </div>
                                </form>
                                    <div class="text-center mt-3">
                                        <a class="small" href="password-recovery.php">Forgot Password?</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <!-- Footer include -->
        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" role="dialog" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Login Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="errorMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault();

            const email = document.querySelector('input[name="email"]').value;
            const password = document.querySelector('input[name="password"]').value;

            fetch('process-login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    'email': email,
                    'password': password
                })
            })
            .then(response => response.text())  // First get the raw response text
            .then(data => {
                try {
                    const jsonData = JSON.parse(data);  // Try to parse as JSON
                    if (jsonData.status === 'success') {
                        // Redirect if successful login
                        window.location.href = jsonData.redirect;
                    } else {
                        // Show the error in the modal
                        showLoginErrorModal(jsonData.message);
                    }
                } catch (error) {
                    showLoginErrorModal('An unexpected error occurred. Please try again later.');
                }
            })
            .catch(error => {
                showLoginErrorModal('An unexpected error occurred. Please try again later.');
            });
        });

        function showLoginErrorModal(message) {
            // Show the error message in the modal body
            const modalBody = document.querySelector('#errorModal .modal-body');
            modalBody.textContent = message;

            // Show the error modal
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        }
    </script>
</body>
</html>
