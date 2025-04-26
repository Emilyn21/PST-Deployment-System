<?php
require('pdf_generator.php'); // Include the reusable function

// Get the JSON data
$data = json_decode(file_get_contents("php://input"), true);
$tableData = $data["tableData"] ?? [];

// Generate PDF using the function
generatePDF($tableData);
?>
