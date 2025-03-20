<?php
session_start();
require 'db.php';  // Connection to accountsinfo
require 'db2.php'; // Connection to users_db

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $selected_accounts = isset($_POST['accounts']) ? $_POST['accounts'] : [];

    // Update user details in users_db
    $stmt = $conn2->prepare("UPDATE users SET fullname=?, email=?, role=?, updated_at=NOW() WHERE username=?");
    $stmt->bind_param("ssss", $fullname, $email, $role, $username);
    $stmt->execute();
    $stmt->close();

    // Fetch currently assigned accounts
    $assignedAccounts = [];
    $query = "SELECT AccountName FROM importantinformation WHERE FIND_IN_SET(?, access)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignedAccounts[] = $row['AccountName'];
    }
    $stmt->close();

    // Determine accounts to remove
    $accountsToRemove = array_diff($assignedAccounts, $selected_accounts);
    foreach ($accountsToRemove as $account) {
        $stmt = $conn->prepare("UPDATE importantinformation SET access = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', access, ','), ?, '')) WHERE AccountName = ?");
        $user_with_comma = ',' . $username . ',';
        $stmt->bind_param("ss", $user_with_comma, $account);
        $stmt->execute();
    }

    // Determine accounts to add
    $accountsToAdd = array_diff($selected_accounts, $assignedAccounts);
    foreach ($accountsToAdd as $account) {
        $stmt = $conn->prepare("UPDATE importantinformation SET access = CONCAT(IFNULL(access, ''), ?, ',') WHERE AccountName = ?");
        $user_with_comma = $username . ',';
        $stmt->bind_param("ss", $user_with_comma, $account);
        $stmt->execute();
    }

    header("Location: admin.php");
    exit();
}
?>
