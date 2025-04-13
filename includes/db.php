<?php
// Database connection
$host = 'localhost';
$db = 'abunetdg_hmamat';
$user = 'abunetdg_amhaslassie';
$pass = '2121@2727Abel';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?> 