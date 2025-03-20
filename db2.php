<?php
$servername2 = "localhost";
$username2 = "root"; // Default XAMPP username
$password2 = "AARRU#champs"; // Default XAMPP password is empty
$dbname2 = "users_db"; // Change this to the users database

// Create a new connection for user authentication database
$conn2 = new mysqli($servername2, $username2, $password2, $dbname2);

// Check connection
if ($conn2->connect_error) {
    die("Connection failed: " . $conn2->connect_error);
}
?>
