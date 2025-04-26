<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Password Recovery</title>
    <link href="css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="css/poppins.css" rel="stylesheet">
    <script src="js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #C5D3E2;
        }
        .card-header {
            background-color: #f8f9fa;
        }
        .form-instruction {
            margin-bottom: 10px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-secondary">
    <!-- Header include -->
    <?php include 'includes/topnav.php'; ?>

    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main class="pb-5">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-header">
                                    <h3 class="text-center font-weight-light my-4">Password Recovery</h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-center form-instruction">Enter your email address below to receive instructions on how to reset your password.</p>
                                        <!-- Error Message Container (Will Only Show If There's an Error) -->
                                        <?php if (!empty($errorMsg)): ?>
                                            <div class="alert alert-danger text-center" role="alert">
                                                <?php echo $errorMsg; ?>
                                            </div>
                                        <?php endif; ?>
                                    <form method="post" action="functions/process-password-reset-email.php">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputEmail" type="email" name="email" placeholder="name@example.com" required />
                                            <label for="inputEmail">Email address</label>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-block" name="reset_password">Reset Password</button>
                                        </div>
                                    </form>
                                    <div class="text-center mt-3">
                                        <a class="small" href="login.php">Go back to Login</a>
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
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
