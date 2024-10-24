<?php
    session_start();
    require 'db_connect.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"])) {
        $task_id = intval($_POST["id"]);
        $user_id = $_SESSION['user_id'];

        // Get the current status of the task
        $sql = "SELECT status FROM tasks WHERE user_id = ? AND id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $user_id, $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_status = $row['status'];

        // Determine the new status
        $new_status = ($current_status === 'completed') ? 'pending' : 'completed';

        // Update task status
        $sql = "UPDATE tasks SET status = ? WHERE user_id = ? AND id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $new_status, $user_id, $task_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Redirect back to the task list
        header("Location: /project_UTS_LAB/");
        exit();
    } else {
        header("Location: /project_UTS_LAB/");
        exit();
    }
?>
