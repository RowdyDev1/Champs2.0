<?php
// db_config.php
$servername = "localhost";
$username = "root";
$password = "AARRU#champs";
$dbname = "accountsinfo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
