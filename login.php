<?php 
session_start();

if(isset($_SESSION['user_id'])){
    header('Location: index.php');
};

$secretKey = "6LepA2oqAAAAALnC9cP6prLqf4F6qACd25MhKOe5";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'db_connect.php';

    // Sanitize and retrieve inputs
    $usernameOrEmail = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);

    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents($verifyUrl . '?secret=' . $secretKey . '&response=' . $recaptchaResponse);
    $responseKeys = json_decode($response, true);

    // Check if recaptcha is successful
    if ($responseKeys["success"]) {
        if (!empty($usernameOrEmail) && !empty($password)) {
            // Prepare SQL statement
            $stmt = $conn->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
            $stmt->bind_param('ss', $usernameOrEmail, $usernameOrEmail); // Bind both username and email
            $stmt->execute();
            $result = $stmt->get_result();

            // Check if user exists
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                // Verify the password
                if (password_verify($password, $row['password'])) {
                    // Successful login
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username']; 

                    header("Location: index.php");
                } else {
                    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>Invalid Password</strong> 
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
                }
            } else {
                echo "User not found.";
            }
        } else {
            echo "Invalid input.";
        }

        // Close the statement and connection
        $stmt->close();
        $conn->close();
    } else {

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
        <div class="border shadow p-4 w-50 w-md-75 w-lg-" style="border-radius: 30px;">
            <h1 class="text-center">Sign In</h1>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 50%;" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">Step 1</div>
            </div>
            <form id="emailForm" class="w-100" method="POST" action="login.php" onsubmit="return validateRecaptcha()">
                <div id="step1" class="collapse show">
                    <span class="fs-sm-3 fs-10">Masukkan Email atau UserName</span>
                    <input type="text" class="form-control form-control-lg" name="email" id="email" placeholder="Enter email" required>
                    <div class="text-danger" id="emailError" style="display: none;">User dengan nama tersebut tak ada</div>
                    <button type="button" class="btn btn-primary" onclick="checkEmailAvailability()">Next</button>
                </div>
                <div id="step2" class="collapse">
                    <h2>Masukkan Password</h2>
                    <input type="password" class="form-control form-control-lg" name="password" id="password" placeholder="Masukkan password" required>
                    <div class="g-recaptcha" data-sitekey="6LepA2oqAAAAAGHv6oYcTt8eb58aaTWky8efK8HY"></div>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <button class="btn btn-primary mt-3" type="button" onclick="showStep(1)">Back</button>
                    <button class="btn btn-success mt-3" type="submit">Submit</button>
                </div>
            </form>
            <a href="signup.php">Doesn't have an account yet?</a>
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
                            window.location.href = 'signup.php';
                        } else {
                            showStep(2); 
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            }
            else{
                if (email) {
                    fetch('check_username_verification.php', {
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
                            emailError.style.display = 'block';
                        } else {
                            emailError.style.display = 'none';
                            showStep(2); 
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
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
                    percentage = 50; 
                    break;
                case 2:
                    percentage = 100; 
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
