<?php 
include 'includes/auth.php';

$query = "SELECT tu.email, tu.profile_picture, tu.contact_number, tu.first_name, tu.middle_name, tu.last_name, 
            TRIM(
                CONCAT(
                    tu.first_name, 
                    CASE 
                        WHEN tu.middle_name IS NOT NULL AND tu.middle_name != '' THEN CONCAT(' ', tu.middle_name) 
                        ELSE '' 
                    END, 
                    ' ', 
                    tu.last_name
                )
            ) AS admin_name,
            CASE 
                WHEN tu.sex IS NULL THEN NULL
                ELSE tu.sex
            END AS sex, tu.birthdate,
            tu.street, tu.barangay, tu.city_municipality, tu.province,
            CASE 
                WHEN COALESCE(tu.street, tu.barangay, tu.city_municipality, tu.province) IS NULL 
                THEN ''
                ELSE TRIM(
                    CONCAT(
                        COALESCE(tu.street, ''),
                        CASE 
                            WHEN TRIM(tu.street) != '' AND TRIM(tu.barangay) != '' THEN ', ' 
                            ELSE '' 
                        END,
                        COALESCE(tu.barangay, ''),
                        CASE 
                            WHEN TRIM(tu.barangay) != '' AND TRIM(tu.city_municipality) != '' THEN ', ' 
                            ELSE '' 
                        END,
                        COALESCE(tu.city_municipality, ''),
                        CASE 
                            WHEN TRIM(tu.city_municipality) != '' AND TRIM(tu.province) != '' THEN ', ' 
                            ELSE '' 
                        END,
                        COALESCE(tu.province, '')
                    )
                )
            END AS address,
            tu.last_login,
            tu.updated_at
          FROM tbl_admin ta
          LEFT JOIN tbl_user tu ON ta.user_id = tu.id
          WHERE tu.id = ?";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Store fetched values
        $email = $row['email'];
        $profile_picture = $row['profile_picture'];
        $contact_number = $row['contact_number'];
        $first_name = $row['first_name'];
        $middle_name = $row['middle_name'];
        $last_name = $row['last_name'];
        $admin_name = $row['admin_name'];
        $sex = $row['sex'] ?? NULL;
        $birthdate = $row['birthdate'];
        $street = $row['street'];
        $barangay = $row['barangay'];
        $city_municipality = $row['city_municipality'];
        $province = $row['province'];
        $address = $row['address'];
        $last_login_time = !empty($row['last_login']) ? date('M. j, Y, g:i A', strtotime($row['last_login'])) : 'N/A';
        $last_profile_update = !empty($row['updated_at']) ? date('M. j, Y, g:i A', strtotime($row['updated_at'])) : 'N/A';
    }
    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}
?>

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
    .timeline {
        border-left: 2px solid #dee2e6;
        padding-left: 1rem;
        margin: 1rem 0;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1rem;
        padding-left: 1rem;
    }

    .timeline-dot {
        width: 10px;
        height: 10px;
        background-color: #007bff;
        border-radius: 50%;
        position: absolute;
        left: -5px;
        top: 5px;
    }

</style>    
<body class="sb-nav-fixed">
    <?php include 'includes/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php include 'includes/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Account</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Account</li>
                    </ol>

                    <!-- Account Information Section -->
                    <section class="card mb-4" role="region" aria-labelledby="account-information">
                        <div class="card-body">
                            <div class="row">
                                <!-- Profile Picture Section -->
                                <div class="col-md-3 text-center">
                                    <h3 id="profile-picture-header">Profile Picture</h3>
                                    <div class="mb-3" role="img" aria-label="User's profile picture">
                                        <?php if (!empty($profile_picture)) { ?>
                                            <img src="data:image/jpeg;base64,<?= base64_encode($profile_picture); ?>" 
                                                 class="img-fluid rounded-circle" 
                                                 alt="Profile Picture" 
                                                 style="width: 200px; height: 200px;">
                                        <?php } else { ?>
                                            <img src="../assets/img/default-image.jpg" 
                                                 class="img-fluid rounded-circle" 
                                                 alt="Default Profile Picture" 
                                                 style="width: 200px; height: 200px;">
                                        <?php } ?>
                                    </div>
                                </div>

                                <!-- Account Details Section -->
                                <div class="col-md-9">
                                    <h3 id="account-information">Account Information</h3>
                                    <div class="mb-2">
                                        <strong>Email address:</strong> 
                                        <span aria-label="Email address"><?= htmlspecialchars($email); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Name:</strong> 
                                        <span aria-label="Full name"><?= htmlspecialchars($admin_name); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Address:</strong> 
                                        <span aria-label="Address"><?= htmlspecialchars($address); ?></span>
                                    </div>

                                    <button class="btn btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#updateProfileModal" 
                                            aria-controls="updateProfileModal">Update Profile</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Add the Recent Activity section here -->
                    <section class="card mb-4" role="region" aria-labelledby="activity-log">
                        <div class="card-body">
                            <h3 id="activity-log">Recent Activity</h3>
                            <ul>
                                <li>Profile updated on <?= htmlspecialchars($last_profile_update); ?></li>
                                <li>Last login: <?= htmlspecialchars($last_login_time); ?></li>
                            </ul>
                        </div>
                    </section>
                </div>
            </main>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" role="dialog" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true" aria-modal="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="updateProfileModalLabel"><i class="fa fa-edit"></i> Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProfileForm" method="POST" enctype="multipart/form-data" action="functions/update-profile.php" aria-labelledby="updateProfileModalLabel">
                        <div class="form-group text-center mb-4" role="img" aria-label="User's profile picture">
                            <img src="data:image/jpeg;base64,<?= base64_encode($profile_picture ?? ''); ?>" 
                                 class="img-fluid rounded-circle mb-3" 
                                 alt="Profile Picture" 
                                 style="width: 150px; height: 150px;">
                            <label for="profile-picture" class="form-label"></label>
                            <input type="file" class="form-control" id="profile-picture" name="profile-picture" accept="image/*" aria-describedby="profile-picture-desc">
                            <div id="profile-picture-desc">Choose a new profile picture in .jpg or .png format.</div>
                        </div>
                        <h4 class="mb-3">Personal Information</h4>
                        <div class="row mb-2">
                            <div class="col-md-6 mb-2">
                                <div class="form-group">
                                    <label for="email">Email Address:</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email); ?>" readonly disabled>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-group">
                                    <label for="contact-number">Contact Number:</label>
                                    <input type="tel" class="form-control" id="contact-number" name="contact-number" placeholder="Enter Contact Number" value="<?= htmlspecialchars($contact_number ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 mb-2">
                                <div class="form-group">
                                    <label for="first-name">First Name:</label>
                                    <input type="text" class="form-control" id="first-name" name="first-name" placeholder="Enter First Name" value="<?= htmlspecialchars($first_name); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="form-group">
                                    <label for="middle-name">Middle Name:</label>
                                    <input type="text" class="form-control" id="middle-name" name="middle-name" placeholder="Enter Middle Name" value="<?= htmlspecialchars($middle_name ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="form-group">
                                    <label for="last-name">Last Name:</label>
                                    <input type="text" class="form-control" id="last-name" name="last-name"  placeholder="Enter Last Name" value="<?= htmlspecialchars($last_name); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6 mb-2">
                                <div class="form-group"> 
                                    <label for="sex">Sex:</label>
                                    <select class="form-select" id="sex" name="sex" aria-label="Select gender">
                                        <option value="" disabled <?= ($sex === null) ? 'selected' : ''; ?>>Select gender</option>
                                        <option value="Male" <?= (strtolower($sex ?? '') == 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?= (strtolower($sex ?? '') == 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?= (strtolower($sex ?? '') == 'other') ? 'selected' : ''; ?>>Prefer not to say</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-group">
                                    <label for="birthdate">Birthdate:</label>
                                    <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?= htmlspecialchars($birthdate ?? ''); ?>">
                                </div>
                            </div></div>

                        <h4 class="mb-3">Address Information</h4>
                        <div class="row mb-2">
                            <div class="col-md-3 mb-2">
                                <div class="form-group">
                                    <label for="street">Street:</label>
                                    <input type="text" class="form-control" id="street" name="street" placeholder="Street" value="<?= htmlspecialchars($street ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-group">
                                    <label for="barangay">Barangay:</label>
                                    <input type="text" class="form-control" id="barangay" name="barangay" placeholder="Enter Barangay" value="<?= htmlspecialchars($barangay ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-group">
                                    <label for="city_municipality">City/Municipality:</label>
                                    <input type="text" class="form-control" id="city_municipality" name="city_municipality" placeholder="Enter City/Municipality" value="<?= htmlspecialchars($city_municipality ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-group">
                                    <label for="province">Province:</label>
                                    <input type="text" class="form-control" id="province" name="province" placeholder="Enter Province" value="<?= htmlspecialchars($province ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="updateProfileForm" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="editSuccessModal" tabindex="-1" aria-labelledby="editSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editSuccessModalLabel"><i class="fas fa-check"></i> Profile Update Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Your account profile has been updated successfully.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="modalCloseBtn" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="editErrorModal" tabindex="-1" aria-labelledby="editErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="editErrorModalLabel"><i class="fas fa-times"></i> Profile Update Failed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    The file size for the selected profile photo is too big. Please upload an image that is within 1MB.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="modalCloseBtn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
    <?php
    if (isset($_SESSION['update_success'])) {
        if ($_SESSION['update_success'] === true) {
            echo "<script>
                    window.onload = function() {
                        var myModal = new bootstrap.Modal(document.getElementById('editSuccessModal'));
                        myModal.show();
                    }
                  </script>";
        } else {
            echo "<script>
                    window.onload = function() {
                        var myModal = new bootstrap.Modal(document.getElementById('editErrorModal'));
                        myModal.show();
                    }
                  </script>";
        }
        unset($_SESSION['update_success']);
    }
    ?>
    <script>
        function openSuccessModal() {
            $('#editSuccessModal').modal('show');
        }

        function openErrorModal() {
            $('#editErrorModal').modal('show');
        }
    </script>
</body>
</html>