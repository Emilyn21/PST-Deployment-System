<?php
include 'auth.php'; // Include your database connection

header('Content-Type: application/json');

// Database connection here ($conn)

// Validate input
if (isset($_GET['school_id']) && is_numeric($_GET['school_id'])) {
    $schoolId = intval($_GET['school_id']);

    $query = "SELECT city, province FROM tbl_school WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param('i', $schoolId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'city' => $row['city']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'School not found.'
            ]);
        }
    } else {
        error_log("SQL Error: " . $conn->error); // Log the SQL error
        echo json_encode([
            'success' => false,
            'message' => 'Database query error.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid school ID.'
    ]);
}
?>
