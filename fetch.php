<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include 'db.php';

// Initialize variable to hold fetched data
$ahtData = [];

// Prepare the SQL query
$sql = "SELECT Month, AHT FROM average_handling_time";

// Prepare statement
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

// Execute the statement
if (!$stmt->execute()) {
    die('Execute failed: ' . htmlspecialchars($stmt->error));
}

$result = $stmt->get_result();

// Fetch all AHT data
while ($row = $result->fetch_assoc()) {
    $ahtData[] = $row;
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Output the data as JSON
header('Content-Type: application/json');
echo json_encode($ahtData);
?>