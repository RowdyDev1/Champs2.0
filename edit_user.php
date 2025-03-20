<?php
session_start();
require 'db.php';  // Connection to accountsinfo
require 'db2.php'; // Connection to users_db

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

// Get user details for editing
if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Fetch user details
    $stmt = $conn2->prepare("SELECT fullname, email, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($fullname, $email, $role);
    $stmt->fetch();
    $stmt->close();

    // Fetch assigned accounts
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

    // Fetch all accounts for dropdown
    $accounts = [];
    $result = $conn->query("SELECT AccountName FROM importantinformation");
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row['AccountName'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Edit User</h2>
    <form action="update_user.php" method="POST">
        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
        
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
                <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="editor" <?php echo ($role == 'editor') ? 'selected' : ''; ?>>Editor</option>
                <option value="viewer" <?php echo ($role == 'viewer') ? 'selected' : ''; ?>>Viewer</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Account Access</label>
            <select name="accounts[]" class="form-control" multiple>
                <?php foreach ($accounts as $account) : ?>
                    <option value="<?php echo htmlspecialchars($account); ?>" 
                        <?php echo in_array($account, $assignedAccounts) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($account); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
		

        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="admin.php" class="btn btn-secondary">Cancel</a>
			<form action="delete_user.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
				<input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
				<button type="submit" class="btn btn-danger mt-2">Delete User</button>
			</form>

    </form>

</div>
</body>
</html>
