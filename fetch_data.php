<?php
// fetch_data.php

// Database credentials
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

// Fetch data from the database
$sql = "SELECT Type, Total_Count FROM fix_no_fix_total";
$result = $conn->query($sql);

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Close the connection
$conn->close();

// Set content type to JSON
header('Content-Type: application/json');
echo json_encode($data);
exit;
?>