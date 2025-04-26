<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark navbar-custom">
    <!-- Navbar Brand -->
    <a class="navbar-brand mx-auto ms-lg-3 ms-3" href="index.php">
        <img src="assets/img/cvsu-logo.png" 
             alt="CvSU Logo" 
             style="width: 30px; height: 30px; object-fit: cover; background-color: transparent; border-radius: 50%;">
        Pre-Service Teacher Deployment System
    </a>

    <!-- Navbar Menu -->
    <div class="navbar-nav-custom">
        <div class="collapse navbar-collapse navbar-nav-custom" id="navbarNav">
            <ul class="navbar-nav ms-auto d-none d-lg-flex">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About Us</a>
                </li>
            </ul>
        </div>

        <!-- Navbar -->
        <ul class="navbar-nav "> <!-- Removed ml-3 and me-lg-auto classes -->
            <li class="nav-item dropdown me-auto d-lg-none">
                <a class="nav-link dropdown-toggle dropdown-toggle-custom" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="index.php">Home</a></li>
                    <li><a class="dropdown-item" href="login.php">Log In</a></li>
                    <li><a class="dropdown-item" href="about.php">About Us</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<style>
    .navbar-brand {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .navbar-nav-custom {
        margin-right: 15px;
    }

    .sidebar-toggle-custom {
        margin-left: 15px; /* Add space to the left */
        padding: 0; /* Adjust padding as needed */
    }

    @media (max-width: 768px) {
        #notificationDropdown {
            display: none;
        }
    }

    .dropdown-toggle-custom {
        padding-left: 0; /* Ensure no extra padding is applied */
    }

    .dropdown-menu {
        right: 0;
        left: auto; /* Ensure dropdown menu is aligned correctly */
    }
</style>
