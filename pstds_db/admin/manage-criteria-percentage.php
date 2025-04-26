<?php 
include 'includes/auth.php';

$stmt = $conn->prepare("SELECT id, eportfolio_percentage, internship_percentage, final_demo_percentage, isActive FROM tbl_evaluation_criteria_percentage WHERE isDeleted = 0 AND isActive = 1");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Manage Evaluation Criteria - Admin</title>
    <link href="../css/jsdelivr.style.min.css" rel="stylesheet" />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/poppins.css" rel="stylesheet">
    <script src="../js/fontawesome.all.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .error-text {
            color: red;
            font-size: 0.9rem;
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
                    <h1 class="mt-5 h3">Manage Evaluation Criteria</h1>
                    <ol class="breadcrumb mb-4" role="navigation" aria-label="Breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Evaluation Criteria</li>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-percentage me-1"></i> Evaluation Criteria Percentages
                        </div>
                        <div class="card-body">
                            <form id="criteriaForm" method="POST" action="functions/update-criteria-percentage.php">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>E-Portfolio (%)</th>
                                            <th>Internship (%)</th>
                                            <th>Final Demo (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()) { ?>
                                            <tr>
                                                <td>
                                                    <input type="number" name="eportfolio_percentage" id="eportfolio" class="form-control" min="0" max="100" step="0.01" value="<?php echo $row['eportfolio_percentage']; ?>" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="internship_percentage" id="internship" class="form-control" min="0" max="100" step="0.01" value="<?php echo $row['internship_percentage']; ?>" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="final_demo_percentage" id="finalDemo" class="form-control" min="0" max="100" step="0.01" value="<?php echo $row['final_demo_percentage']; ?>" required>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <div class="text-end">
                                    <span class="error-text d-none" id="errorText">The total percentage must equal 100%.</span>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="successModalLabel">Success</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p id="successMessage"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="errorModalLabel">Error</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p id="errorMessage"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script>
document.getElementById('criteriaForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    const formData = new FormData(this);

    fetch('functions/update-criteria-percentage.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success modal with dynamic message
            document.getElementById('successMessage').innerText = data.success;
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        } else if (data.error) {
            // Show error modal with dynamic message
            document.getElementById('errorMessage').innerText = data.error;
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        }
    })
    .catch(() => {
        // Handle unexpected errors
        document.getElementById('errorMessage').innerText = 'An unexpected error occurred. Please try again later.';
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
    });
});


    </script>
</body>

</html>
