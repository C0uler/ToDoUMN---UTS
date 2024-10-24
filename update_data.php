<?php
session_start();
require 'db_connect.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars(trim($_POST['email']));
    $username = htmlspecialchars(trim($_POST['username']));
    $old_password = htmlspecialchars(trim($_POST['old_password']));
    $new_password = htmlspecialchars(trim($_POST['new_password']));

    if (!empty($old_password) && !empty($new_password)) {
      
        $checkPasswordStmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $checkPasswordStmt->bind_param("s", $user_id);
        $checkPasswordStmt->execute();
        $passwordResult = $checkPasswordStmt->get_result();
        $user = $passwordResult->fetch_assoc();

        if (password_verify($old_password, $user['password'])) {
           
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $updatePasswordStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updatePasswordStmt->bind_param("ss", $new_password_hashed, $user_id);

            if ($updatePasswordStmt->execute()) {
                $_SESSION['success'] = "Password updated successfully.";
                header("location: /project_UTS_lab/index.php");
            } else {
                $_SESSION['error'] = "Error updating password: " . $updatePasswordStmt->error;
                header("location: /project_UTS_lab/index.php");
            }
            $updatePasswordStmt->close();
        } else {
            $_SESSION['error'] = "Old password is incorrect.";
            header("location: /project_UTS_lab/index.php");
        }
        $checkPasswordStmt->close();
    }

    else if (!empty($user_id)) {
        // Check if email is already in use by another user
        $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmailStmt->bind_param("ss", $email, $user_id);
        $checkEmailStmt->execute();
        $emailResult = $checkEmailStmt->get_result();

        // Check if username is already in use by another user
        $checkUsernameStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkUsernameStmt->bind_param("ss", $username, $user_id);
        $checkUsernameStmt->execute();
        $usernameResult = $checkUsernameStmt->get_result();

        if ($emailResult->num_rows > 0) {
            $_SESSION['error'] = "Email is already in use.";
            header("location: /project_UTS_lab/index.php");
            exit();
        } elseif ($usernameResult->num_rows > 0) {
            $_SESSION['error'] = "Username is already in use.";
            header("location: /project_UTS_lab/index.php");
            exit();
        } else {
            // Update email and username
            $stmt = $conn->prepare("UPDATE users SET email = ?, username = ? WHERE id = ?");
            $stmt->bind_param("sss", $email, $username, $user_id);

            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                header("location: /project_UTS_lab/index.php");
                exit();
            } else {
                $_SESSION['error'] = "Error: " . $stmt->error;
                header("location: /project_UTS_lab/index.php");
                exit();
            }
            $stmt->close();
        }
        $checkEmailStmt->close();
        $checkUsernameStmt->close();
    } else {
        $_SESSION['error'] = "User ID is missing.";
        header("location: /project_UTS_lab/index.php");
        exit();
    }
} else {
    header("location: /project_UTS_lab/index.php");
    exit();
}

$conn->close();
?>
