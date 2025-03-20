<?php
session_start();
require 'db.php';  // Connection to accountsinfo
require 'db2.php'; // Connection to users_db

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    $username = $_POST['username'];

    // Remove user from access column in importantinformation
    $stmt = $conn->prepare("UPDATE importantinformation SET access = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', access, ','), ?, '')) WHERE FIND_IN_SET(?, access)");
    $user_with_comma = ',' . $username . ',';
    $stmt->bind_param("ss", $user_with_comma, $username);
    $stmt->execute();
    $stmt->close();

    // Delete user from users_db
    $stmt = $conn2->prepare("DELETE FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php");
    exit();
}
?>
