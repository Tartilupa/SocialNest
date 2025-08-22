<?php
$host = "localhost"; // MySQL Hostname
$username = "root"; // Database Username
$password = ""; // Database Password
$dbname = "user"; // Database Name

$conn = new mysqli($host, $username, $password, $dbname);

// Preveri povezavo
if ($conn->connect_error) {
    die("Povezava ni uspela: " . $conn->connect_error);
}
?>
