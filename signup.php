<?php


$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);


function generateUserId($conn) {

    $stmt = $conn->prepare("SELECT id FROM users ORDER BY id DESC LIMIT 1");
    $stmt -> execute();
    $result = $stmt -> get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $lastUserId = $row['id'];

        // Mengambil angka dari User ID terakhir
        $number = (int) substr($lastUserId, 1); // Menghilangkan 'U' dan mengkonversi ke integer
        $newNumber = $number + 1; // Increment angka
    } else {
        $newNumber = 1; // Jika tidak ada data, mulai dari 1
    }

    return 'U' . str_pad($newNumber, 9, '0', STR_PAD_LEFT);
}

function generateUserList($conn) {
    // Ambil User ID terakhir dari database
    // var_dump($conn);
    $stmt = $conn->prepare("SELECT id FROM list ORDER BY id DESC LIMIT 1");
    $stmt -> execute();
    $result = $stmt -> get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        var_dump($row);
        $lastUserId = $row['id'];


        $number = (int) substr($lastUserId, 1); 
        $newNumber = $number + 1;
    } else {
        $newNumber = 1; 
    }

    return 'L' . str_pad($newNumber, 10, '0', STR_PAD_LEFT);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Database connection
    require 'db_connect.php';

    $secretKey = "6LepA2oqAAAAALnC9cP6prLqf4F6qACd25MhKOe5";

    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);


    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents($verifyUrl . '?secret=' . $secretKey . '&response=' . $recaptchaResponse);
    $responseKeys = json_decode($response, true);

    // Check if recaptcha is successful
    if ($responseKeys["success"]) {



        // Check input
        if (!empty($username) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($password)) {
            // Check if user exists
            $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
            $stmt->bind_param('ss', $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();
        
            if ($result->num_rows > 0) {
                // $_SESSION['error'] =  "User already exists.";
                echo json_encode(['available' => false]);
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $generatedid = generateUserId($conn);
                $stmt = $conn->prepare('INSERT INTO users(id, username, email, password) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $generatedid, $username, $email, $hashed_password);

                if ($stmt->execute()) {
                    $listname = "My Task";
                    $list_id = generateUserList($conn);
                    $stmt = $conn->prepare('INSERT INTO list(id, user_id, name) VALUES (?, ?, ?)');
                    $stmt->bind_param('sss', $list_id, $generatedid, $listname);
                    $stmt->execute();

                    header("location: /Project_UTS_lab/login.php");
                } else {
                    // $_SESSION['error'] = "Error: " . $conn->error;
                    echo json_encode(['available' => false]);
                }
            }
        } else {
            if ($result->num_rows > 0) {
                // $_SESSION['error'] =  "invalud input.";
                echo json_encode(['available' => false]);
        }
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="././public/css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container d-flex flex-column justify-content-center align-items-center" style="height: 100vh;">
        <div class="border shadow p-4 w-100 w-sm-75 w-md-60 w-lg-50" id="responsive-div" style="border-radius: 30px;">
            <h1 class="text-center">Sign Up</h1>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 33%;" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">Step 1</div>
            </div>
            <form id="emailForm" class="w-100" method="post" action="signup.php" onsubmit="return validateRecaptcha()">
                <div id="step1" class="collapse show">
                    <h2>Step 1: Masukkan Email</h2>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter email" required>
                    <div class="text-danger" id="emailError" style="display: none;">Email telah dipakai.</div>
                    <button type="button" class="btn btn-primary" onclick="checkEmailAvailability()">Next</button>
                </div>
                <div id="step2" class="collapse">
                    <h2>Step 2: Masukkan Username</h2>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Enter username" required>
                    <div class="text-danger" id="usernameError" style="display: none;">UserName Telah dipakai.</div>
                    <button class="btn btn-primary mt-3" type="button" onclick="showStep(1)">Back</button>
                    <button class="btn btn-primary mt-3" type="button" onclick="validateUsername()">Next</button>
                </div>
                <div id="step3" class="collapse">
                    <h2>Step 3: Masukkan Password</h2>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Masukkan password" required>
                    <div class="g-recaptcha" data-sitekey="6LepA2oqAAAAAGHv6oYcTt8eb58aaTWky8efK8HY"></div>
                    <button class="btn btn-primary mt-3" type="button" onclick="showStep(2)">Back</button>
                    <button class="btn btn-success mt-3" type="submit">Submit</button>
                </div>
            </form>
            <a href="login.php">Already had an account?</a>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkEmailAvailability() {
            const email = document.getElementById('email').value;
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const emailError = document.getElementById('emailError');
           
            if(regex.test(email)){
                if (email) {
                    fetch('check_email_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'email=' + encodeURIComponent(email)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        if (data.available) {
                            emailError.style.display = 'none';
                            showStep(2); // Proceed to the next step
                        } else {
                            window.location.href = 'login.php';
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            }
            else{
                emailError.innerHTML = "Email tidak valid";
                emailError.style.display = 'block';
            }
        }


        function validateUsername() {
            const username = document.getElementById('username').value;
            const usernameError = document.getElementById('usernameError');

            if (username) {
                    fetch('check_username_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'email=' + encodeURIComponent(username)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        if (data.available) {
                            usernameError.style.display = 'none';
                            showStep(3);
                        } else {
                            usernameError.style.display = 'block';
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
        }

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.collapse').forEach(function(el) {
                el.classList.remove('show');
            });

            // Show the requested step
            document.getElementById('step' + step).classList.add('show');
            updateProgressBar(step); // Update progress bar
        }

        function updateProgressBar(step) {
            const progressBar = document.getElementById('progress-bar');
            let percentage;

            switch (step) {
                case 1:
                    percentage = 33; // 1 out of 3
                    break;
                case 2:
                    percentage = 66; // 2 out of 3
                    break;
                case 3:
                    percentage = 100; // 3 out of 3
                    break;
                default:
                    percentage = 0;
                    break;
            }

            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            progressBar.innerText = 'Step ' + step; // Update progress text
        }

        function validateRecaptcha() {
        var response = grecaptcha.getResponse();
        if (response.length === 0) {
            alert("Please complete the reCAPTCHA.");
            return false;
        }
        return true;
    }
    </script>
</body>
</html>
