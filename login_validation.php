<?php 
    $secretKey = "6LepA2oqAAAAALnC9cP6prLqf4F6qACd25MhKOe5";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        require 'db_connect.php';
    
        $usernameOrEmail = htmlspecialchars(trim($_POST['email']));
        $password = trim($_POST['password']);
        $recaptchaResponse = $_POST['g-recaptcha-response'];
    
        // reCAPTCHA verification
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $response = file_get_contents($verifyUrl . '?secret=' . $secretKey . '&response=' . $recaptchaResponse);
        $responseKeys = json_decode($response, true);
    
        if ($responseKeys["success"]) {
            if (!empty($usernameOrEmail) && !empty($password)) {
                $stmt = $conn->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
                $stmt->bind_param('ss', $usernameOrEmail, $usernameOrEmail);
                $stmt->execute();
                $result = $stmt->get_result();
    
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (password_verify($password, $row['password'])) {
                        // Successful login
                        session_start();
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
    
                        echo json_encode(['available' => true]); // Response untuk AJAX
                    } else {
                        echo json_encode(['available' => false]); // Invalid password
                    }
                } else {
                    echo json_encode(['available' => false]); // User tidak ditemukan
                }
            } else {
                echo json_encode(['available' => false]); // Input kosong
            }
    
            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(['recaptcha' => false]); // reCAPTCHA gagal
        }
    }
?>