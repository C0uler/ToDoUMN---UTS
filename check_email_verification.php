<?php 
require 'db_connect.php'; // Ensure you include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any user with that email exists
    if ($result->num_rows > 0) {
        // Email is not available
        echo json_encode(['available' => false]);
    } else {
        // Email is available
        echo json_encode(['available' => true]);
    }

    $stmt->close();
    $conn->close();
}
?>
