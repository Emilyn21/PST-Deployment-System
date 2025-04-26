<?php
include 'auth.php'; // Authentication

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Retrieve and validate inputs
        $eportfolio_percentage = isset($_POST['eportfolio_percentage']) ? (float)$_POST['eportfolio_percentage'] : 0;
        $internship_percentage = isset($_POST['internship_percentage']) ? (float)$_POST['internship_percentage'] : 0;
        $final_demo_percentage = isset($_POST['final_demo_percentage']) ? (float)$_POST['final_demo_percentage'] : 0;

        $total = $eportfolio_percentage + $internship_percentage + $final_demo_percentage;

        // Check if the total is valid
        if (abs($total - 100) > 0.01) {
            http_response_code(400);
            echo json_encode(['error' => "The total percentage is ${total}%. It must equal 100%."]);
            exit;
        }

        // Prepare the update query
        $stmt = $conn->prepare("
            UPDATE tbl_evaluation_criteria_percentage
            SET eportfolio_percentage = ?, internship_percentage = ?, final_demo_percentage = ?
            WHERE isDeleted = 0 AND isActive = 1
        ");
        $stmt->bind_param('ddd', $eportfolio_percentage, $internship_percentage, $final_demo_percentage);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Evaluation criteria percentages updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update the evaluation criteria percentages due to a database error.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method. Only POST is allowed.']);
}
?>
