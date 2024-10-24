<?php
    session_start();
    require 'db_connect.php';
    date_default_timezone_set("Asia/Jakarta");
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $title = htmlspecialchars(trim($_POST['title']));
        $description = htmlspecialchars(trim($_POST['description']));
        $due_date = htmlspecialchars(trim($_POST['date']));
        if(!$due_date){
            $todaydate = date('Y-m-d');
            $due_date = $todaydate . ' '. '23:59:59';
        }
        $user_id = $_SESSION['user_id']; // Mengambil ID pengguna dari session
        if(!$_SESSION['list_id']){
            $defaultName = "MY Task";
            $stmt = $conn->prepare("SELECT id FROM list WHERE user_id = ? AND name = ?");
            $stmt->bind_param('ss', $user_id, $defaultName);
            $stmt->execute();
            $result = $stmt -> get_result();
            $row = $result->fetch_assoc();
            
            $defaultListid = $row['id'];
        }
        else{
            $defaultListid = $_SESSION['list_id'];
        }
        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, list_id, title, description, due_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $user_id, $defaultListid,$title, $description, $due_date);
            
            if ($stmt->execute()) {
                echo "Tugas berhasil ditambahkan.";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
            header("location: index.php");
            exit();
        } else {
            echo "Judul tugas tidak boleh kosong.";
        }
    }
    else{
        header("location: index.php");
    }
    $conn->close();    
?>