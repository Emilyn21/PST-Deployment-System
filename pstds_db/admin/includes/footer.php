<?php
// Check if the script is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'footer.php') {
    header('Location: ../index.php'); // Redirect to login page
    exit();
}
?>
<footer class="py-4 bg-light mt-auto">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; 2023 Cavite State University - CEdTED. All rights reserved.</div>
            <div>
                <a href="#">Privacy Policy</a>
                &middot;
                <a href="#">Terms &amp; Conditions</a>
            </div>
        </div>
    </div>
</footer>
