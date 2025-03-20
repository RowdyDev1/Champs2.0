<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db2.php'; // Include the user database

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Fetch user details from the users_db database
$username = $_SESSION['username'];

$sql = "SELECT id, email, fullname, role FROM users WHERE username = ?";
$stmt = $conn2->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Store user details in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'] ?? 'N/A'; // Avoid undefined index warning
    $_SESSION['fullname'] = $user['fullname'] ?? 'Guest'; // Avoid undefined index warning
    $_SESSION['role'] = $user['role']; // Store user role in session

    // Log user login
    logToFile("User logged in");
} else {
    // If user not found, log out
    session_destroy();
    header('Location: login.php');
    exit();
}

/**
 * Function to log user activity
 */
function logToFile($message)
{
    $logFile = 'logs.txt';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User-Agent';
    $timestamp = date("Y-m-d H:i:s");

    // Check if username and role are set in session
    $username = $_SESSION['username'] ?? 'Unknown User';
    $role = $_SESSION['role'] ?? 'Unknown Role';

    $logEntry = "[$timestamp] - IP: $ipAddress - User: $username - Role: $role - Action: $message - User-Agent: $userAgent" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Example Usage (Logging page visits)
logToFile("User visited " . $_SERVER['PHP_SELF']);

// Close statement and connection
$stmt->close();
$conn2->close();
?>
