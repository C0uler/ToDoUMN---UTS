<?php
    session_start();
    require 'db_connect.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])) {
        $task_id = intval($_POST["delete"]);
        $user_id = $_SESSION['user_id'];
    
        // Delete task
        $sql = "DELETE FROM tasks WHERE user_id = ? AND id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $user_id, $task_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    
        // Unset Delete_Permission
        $_SESSION["Delete_Permission"] = NULL;
    
        // Redirect back to the task list
        header("Location: /project_UTS_LAB/");
        exit();
    } else {
        header("Location: /project_UTS_LAB/");
        exit();
    };
?>