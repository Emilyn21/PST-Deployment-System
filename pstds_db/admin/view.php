<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Account</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js" crossorigin="anonymous"></script>
</head>
<style>
    body {
        font-family: 'Poppins', sans-serif;
    }
</style>    
<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Search</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Search</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <h3>Profile Picture</h3>
                                    <!-- Icon to show current profile picture -->
                                    <div class="text-center mb-3">
                                        <img src="https://via.placeholder.com/150" class="img-fluid rounded-circle mb-3" alt="Profile Picture">
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <h3>Account Information</h3>
                                    <p>Email address: john.doe@example.com</p>
                                    <p>Name: John Michael Doe</p>
                                    <p>Program and major: Bachelor of Secondary Education Major in English</p>
                                    <p>Academic year: 2024-2025</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h3>Deployment Information</h3>
                            <p>Placed School: Cavite National High School</p>
                            <p>Adviser: Christian Corvera</p>
                            <p>Cooperating Teacher's Name: Ma. Lourdes Liwanag</p>
                            <p class="mb-0">Deployment Date: July 20-September 20, 2024</p>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
