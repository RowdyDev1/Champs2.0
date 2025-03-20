<?php
require 'config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        try {
            // Fetch user from database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($old_password, $user['password'])) {
                // Encrypt new password
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in database
                $update_stmt = $pdo->prepare("UPDATE users SET password = :new_password WHERE username = :username");
                $result = $update_stmt->execute(['new_password' => $new_password_hashed, 'username' => $username]);

                if ($result) {
                    $success = "Password successfully updated.";
                } else {
                    $error = "Failed to update password.";
                    $errorInfo = $update_stmt->errorInfo();
                    error_log("SQL Error: " . implode(", ", $errorInfo));
                }
            } else {
                $error = "Invalid username or old password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Champs</title>
  <!-- base:css -->
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Custom CSS for enhanced visuals -->
  <link rel="stylesheet" href="css/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />
  <style>
    body {
        background: url('images/Back24.png') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Roboto', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        margin: 0;
    }
    .auth-card {
        background: rgba(255, 255, 255, 0.85); /* Slight transparency */
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        width: 100%;
        padding: 40px;
        box-sizing: border-box;
    }
    .auth-card h2 {
        margin-bottom: 20px;
        font-size: 1.5rem;
        color: #333;
        text-align: center;
    }
    .auth-card .form-control {
        border-radius: 30px;
        padding: 15px 20px;
    }
    .auth-card .btn-primary {
        background: #6B73FF;
        border: none;
        border-radius: 30px;
        transition: background 0.3s ease;
    }
    .auth-card .btn-primary:hover {
        background: #000DFF;
    }
    .auth-card .btn-secondary {
        background: #6B73FF;
        border: none;
        border-radius: 30px;
        transition: background 0.3s ease;
    }
    .auth-card .btn-secondary:hover {
        background: #000DFF;
    }
    .alert {
        padding: 10px;
        border-radius: 30px;
        text-align: center;
        font-size: 0.9rem;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }
  </style>
</head>

<body>
  <div class="auth-card">
    <h2>Reset Password</h2>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="post" action="reset_password.php">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
      </div>
      <div class="form-group">
        <label for="oldPassword">Old Password</label>
        <input type="password" class="form-control" id="oldPassword" name="old_password" required>
      </div>
      <div class="form-group">
        <label for="newPassword">New Password</label>
        <input type="password" class="form-control" id="newPassword" name="new_password" required>
      </div>
      <div class="form-group">
        <label for="confirmPassword">Confirm New Password</label>
        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
      </div>
      <div class="d-flex justify-content-between">
        <button type="submit" class="btn btn-primary">Reset Password</button>
        <a href="login.php" class="btn btn-secondary">Back to Login</a>
      </div>
    </form>
  </div>
  <!-- base:js -->
  <script src="vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <script src="js/jquery.cookie.js" type="text/javascript"></script>
  <!-- inject:js -->
  <script src="js/off-canvas.js"></script>
  <script src="js/hoverable-collapse.js"></script>
  <script src="js/template.js"></script>
  <!-- endinject -->
</body>

</html>