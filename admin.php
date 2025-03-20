<?php
session_start();
require 'db.php';  // Connection to accountsinfo
require 'db2.php'; // Connection to users_db

// Fetch accounts from importantinformation table
$accounts = [];
$result = $conn->query("SELECT AccountName FROM importantinformation");
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row['AccountName'];
}

// Fetch users and their assigned accounts
$users = [];
$result2 = $conn2->query("SELECT * FROM users");
while ($row = $result2->fetch_assoc()) {
    $username = $row['username'];
    
    // Fetch assigned accounts from importantinformation table
    $stmt = $conn->prepare("SELECT AccountName FROM importantinformation WHERE FIND_IN_SET(?, access)");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $assigned_accounts = [];
    $result_accounts = $stmt->get_result();
    while ($account_row = $result_accounts->fetch_assoc()) {
        $assigned_accounts[] = $account_row['AccountName'];
    }
    $stmt->close();

    // Add created_at and updated_at values
    $row['assigned_accounts'] = implode(", ", $assigned_accounts);
    $row['created_at'] = $row['created_at']; // Already fetched
    $row['updated_at'] = $row['updated_at']; // Already fetched
    $users[] = $row;
}


// Handle new user submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Encrypt password
    $selected_accounts = isset($_POST['accounts']) ? $_POST['accounts'] : [];
    
    // Insert user into users_db
    $stmt = $conn2->prepare("INSERT INTO users (username, password, email, fullname, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $username, $password, $email, $fullname, $role);
    $stmt->execute();
    
    // Update access column in importantinformation
    foreach ($selected_accounts as $account) {
        // Get current access users
        $stmt = $conn->prepare("SELECT access FROM importantinformation WHERE AccountName = ?");
        $stmt->bind_param("s", $account);
        $stmt->execute();
        $stmt->bind_result($current_access);
        $stmt->fetch();
        $stmt->close();

        // Prevent duplicate usernames
        $updated_access = array_filter(array_unique(array_merge(explode(",", $current_access), [$username])));
        $updated_access_string = implode(",", $updated_access);

        $stmt = $conn->prepare("UPDATE importantinformation SET access = ? WHERE AccountName = ?");
        $stmt->bind_param("ss", $updated_access_string, $account);
        $stmt->execute();
    }
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Admin Panel - Manage Users</h2>
        
        <!-- Add User Form -->
        <form method="POST">
            <div class="mb-3">
                <label>Username:</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Full Name:</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Role:</label>
                <select name="role" class="form-control" required>
                    <option value="admin">Admin</option>
                    <option value="editor">Editor</option>
                    <option value="viewer">Viewer</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Assign Accounts:</label>
                <select name="accounts[]" class="form-control" multiple>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo htmlspecialchars($account); ?>"><?php echo htmlspecialchars($account); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
        </form>

        <!-- User Table -->
        <h3 class="mt-5">Existing Users</h3>
		<div class="row mt-3">
    <div class="col-md-3">
        <label>Search Username:</label>
        <input type="text" id="searchUsername" class="form-control" placeholder="Enter username...">
    </div>
    <div class="col-md-3">
        <label>Filter by Role:</label>
        <select id="filterRole" class="form-control">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="editor">Editor</option>
            <option value="viewer">Viewer</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>Filter by Account:</label>
        <select id="filterAccount" class="form-control" multiple>
            <option value="">All Accounts</option>
            <?php foreach ($accounts as $account): ?>
                <option value="<?php echo htmlspecialchars($account); ?>"><?php echo htmlspecialchars($account); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

        <table class="table table-bordered mt-3">
<thead>
    <tr>
        <th>Username</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Assigned Accounts</th>
        <th>Created At</th>
        <th>Updated At</th>
        <th>Actions</th>
    </tr>
</thead>

<tbody>
    <?php foreach ($users as $user): ?>
    <tr>
        <td><?php echo htmlspecialchars($user['username']); ?></td>
        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
        <td><?php echo htmlspecialchars($user['email']); ?></td>
        <td><?php echo htmlspecialchars($user['role']); ?></td>
        <td><?php echo htmlspecialchars($user['assigned_accounts']); ?></td>
        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
        <td><?php echo htmlspecialchars($user['updated_at']); ?></td>
        <td>
            <a href="edit_user.php?username=<?php echo $user['username']; ?>" class="btn btn-warning">Edit</a>
            <form action="delete_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </div>
	<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchUsername = document.getElementById("searchUsername");
    const filterRole = document.getElementById("filterRole");
    const filterAccount = document.getElementById("filterAccount");
    const tableRows = document.querySelectorAll("tbody tr");

    function filterTable() {
        let usernameValue = searchUsername.value.toLowerCase();
        let roleValue = filterRole.value.toLowerCase();
        let selectedAccounts = Array.from(filterAccount.selectedOptions).map(opt => opt.value.toLowerCase());

        tableRows.forEach(row => {
            let username = row.cells[0].textContent.toLowerCase();
            let role = row.cells[3].textContent.toLowerCase();
            let accounts = row.cells[4].textContent.toLowerCase().split(", ");

            let usernameMatch = username.includes(usernameValue);
            let roleMatch = roleValue === "" || role === roleValue;
            let accountMatch = selectedAccounts.length === 0 || selectedAccounts.some(acc => accounts.includes(acc));

            row.style.display = (usernameMatch && roleMatch && accountMatch) ? "" : "none";
        });
    }

    searchUsername.addEventListener("keyup", filterTable);
    filterRole.addEventListener("change", filterTable);
    filterAccount.addEventListener("change", filterTable);
});
</script>

</body>
</html>
