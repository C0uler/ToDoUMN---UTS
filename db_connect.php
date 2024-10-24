<?php
$servername = "localhost";
$username = "root";  // mysql username
$password = "";      // mysql password
$dbname = "auth_db";  // nama datbase

// bikin conn
$conn = new mysqli($servername, $username, $password, $dbname);

// cek conn
if ($conn->connect_error) {
    die("Connection fail: " . $conn->connect_error);
}
?>
