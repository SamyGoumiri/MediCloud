<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hippocare';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ensure consistent encoding for accented names and emails
if (!mysqli_set_charset($conn, 'utf8mb4')) {
    die("Failed to set database charset: " . mysqli_error($conn));
}
?>
