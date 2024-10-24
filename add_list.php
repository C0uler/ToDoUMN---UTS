<?php
    session_start();
    require 'db_connect.php';
    
    function generateUserId($conn) {

        $stmt = $conn->prepare("SELECT id FROM list ORDER BY id DESC LIMIT 1");
        $stmt -> execute();
        $result = $stmt -> get_result();
    
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
    
            $lastUserId = $row['id'];
    
            // Mengambil angka dari User ID terakhir
            $number = (int) substr($lastUserId, 1);
            $newNumber = $number + 1; // Increment angka
        } else {
            $newNumber = 1; // Jika tidak ada data, mulai dari 1
        }
    
        return 'L' . str_pad($newNumber, 9, '0', STR_PAD_LEFT);
    }


    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["list"])) {
        $title = htmlspecialchars(trim($_POST['list']));
        $user_id = $_SESSION['user_id']; 
        $defaultListid = generateUserId($conn);
        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO list (user_id, id, name) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $user_id, $defaultListid, $title);
            if ($stmt->execute()) {
                echo "Tugas berhasil ditambahkan.";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
            header("location: /project_UTS_lab/index.php");
            exit();
        } else {
            echo "Judul tugas tidak boleh kosong.";
        }
    }
    else{
        header("location: /project_UTS_lab/index.php");
    }
    $conn->close(); 
    
?>