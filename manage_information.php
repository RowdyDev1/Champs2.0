<?php
session_start();
include 'db_config.php'; // Ensure this file contains the correct DB connection settings

$information_list = [];
$account_name = '';
$update_message = ''; // Initialize the update message variable

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Fetch all account names for the dropdown
$account_stmt = $conn->prepare("SELECT DISTINCT AccountName FROM importantinformation ORDER BY AccountName ASC");
$account_stmt->execute();
$account_result = $account_stmt->get_result();
$accounts = $account_result->fetch_all(MYSQLI_ASSOC);
$account_stmt->close();

// Check if an account name is passed via the URL
if (isset($_GET['account_name'])) {
    $account_name = $_GET['account_name'];

    // Prepare and execute the SQL query to fetch the data
    $stmt = $conn->prepare("SELECT information FROM importantinformation WHERE AccountName = ?");
    $stmt->bind_param("s", $account_name);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if data was found
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $information_list[] = $row;
        }
    } else {
        $information_list[] = ["information" => "No important information found."];
    }

    // Close the statement
    $stmt->close();
}

// Handle updating of information
if (isset($_POST['update_info']) && isset($_POST['new_info']) && !empty($_POST['account_name'])) {
    $new_info = trim($_POST['new_info']);
    $old_info = $_POST['old_info'];
    $account_name = $_POST['account_name'];

    // Limit text to 400 characters
    if (strlen($new_info) > 400) {
        $_SESSION['message'] = "Error: Information cannot exceed 400 characters.";
    } else {
        $update_stmt = $conn->prepare("UPDATE importantinformation SET information = ? WHERE information = ? AND AccountName = ?");
        $update_stmt->bind_param("sss", $new_info, $old_info, $account_name);
        $update_stmt->execute();
        $update_stmt->close();

        $_SESSION['message'] = "Important Information Updated";
    }

    // Redirect back to the account page
    header("Location: index.php?name=" . urlencode($account_name));
    exit();
}

// Display update message if available
if (isset($_SESSION['message'])) {
    $update_message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get the referring page URL if available
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Information</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Manage Important Information</h1>

        <!-- Display update message -->
        <?php if (!empty($update_message)): ?>
            <div class="alert <?php echo (strpos($update_message, 'Error') !== false) ? 'alert-danger' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($update_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($information_list)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Information</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($information_list as $info): ?>
                        <tr>
                            <form method="POST" action="manage_information.php">
                                <td>
                                    <textarea name="new_info" class="form-control" maxlength="400"><?php echo htmlspecialchars($info['information']); ?></textarea>
                                    <input type="hidden" name="old_info" value="<?php echo htmlspecialchars($info['information']); ?>">
                                    <input type="hidden" name="account_name" value="<?php echo htmlspecialchars($account_name); ?>">
                                </td>
                                <td>
                                    <button type="submit" name="update_info" class="btn btn-primary">Update</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No information available for this account.</p>
        <?php endif; ?>

        <!-- Back button -->
        <a href="<?php echo htmlspecialchars($referrer); ?>" class="btn btn-secondary mt-3">Cancel</a>
    </div>
</body>
</html>
