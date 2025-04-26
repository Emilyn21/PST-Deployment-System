<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Search - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .search-results {
            margin-top: 20px;
        }
        .search-results .result-category {
            margin-top: 20px;
        }
        .search-results .result-category h4 {
            margin-bottom: 10px;
            color: #2F4472;
        }
        .search-results .result-item {
            border-bottom: 1px solid #ccc;
            padding: 10px 0;
        }
        .search-results .result-item:last-child {
            border-bottom: none;
        }
        .search-results .result-item h5 {
            margin: 0;
            font-size: 18px;
            color: #2F4472;
        }
        .search-results .result-item p {
            margin: 5px 0;
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
                    <h1 class="mt-4">Search</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Search</li>
                    </ol>

                    <!-- Search Results Section -->
                    <div class="search-results">
                        <h3>Search Results for "Query"</h3>

                        <!-- Pre-Service Teachers -->
                        <div class="result-category">
                            <h4>Pre-Service Teachers</h4>
                            <div class="result-item">
                                <h5><a href="view.php">Pre-Service Teacher 1</a></h5>
                                <p>Brief description of the pre-service teacher goes here.</p>
                            </div>
                            <div class="result-item">
                                <h5><a href="view.php">Pre-Service Teacher 2</a></h5>
                                <p>Brief description of the pre-service teacher goes here.</p>
                            </div>
                            <!-- Add more result items as needed -->
                        </div>

                        <!-- Advisers -->
                        <div class="result-category">
                            <h4>Advisers</h4>
                            <div class="result-item">
                                <h5>Adviser 1</h5>
                                <p>Brief description of the adviser goes here.</p>
                            </div>
                            <div class="result-item">
                                <h5>Adviser 2</h5>
                                <p>Brief description of the adviser goes here.</p>
                            </div>
                            <!-- Add more result items as needed -->
                        </div>

                        <!-- Cooperating Schools -->
                        <div class="result-category">
                            <h4>Cooperating Schools</h4>
                            <div class="result-item">
                                <h5>School 1</h5>
                                <p>Brief description of the cooperating school goes here.</p>
                            </div>
                            <div class="result-item">
                                <h5>School 2</h5>
                                <p>Brief description of the cooperating school goes here.</p>
                            </div>
                            <!-- Add more result items as needed -->
                        </div>

                        <!-- Cooperating Teachers -->
                        <div class="result-category">
                            <h4>Cooperating Teachers</h4>
                            <div class="result-item">
                                <h5>Cooperating Teacher 1</h5>
                                <p>Brief description of the cooperating teacher goes here.</p>
                            </div>
                            <div class="result-item">
                                <h5>Cooperating Teacher 2</h5>
                                <p>Brief description of the cooperating teacher goes here.</p>
                            </div>
                            <!-- Add more result items as needed -->
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
