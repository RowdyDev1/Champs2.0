<?php
session_start();
require 'config.php'; // Include database connection
$error = '';

// MySQLi connection for logging user actions
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to log user actions
function logUserAction($username, $action) {
    global $conn;

    // Get user IP address and user agent
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO user_logs (username, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssss", $username, $action, $ipAddress, $userAgent);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Log entry created successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $user['id']; // Assuming user ID is stored in the database
        logUserAction($username, 'Logged in');
        header('Location: dashboard.php');
        exit();
    } else {
        // Login failed
        $error = "Invalid username or password";
    }
}

// Example of logging other actions
if (isset($_SESSION['username'])) {
    logUserAction($_SESSION['username'], 'Accessed dashboard');
}

// Close the database connection
$conn->close();
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
    .auth-card h4 {
        margin-bottom: 20px;
        font-size: 1.5rem;
        color: #333;
        text-align: center;
    }
    .auth-card .form-control {
        border-radius: 30px !important; /* Apply rounding to both ends */
        padding: 15px 20px;
    }
    .auth-card .input-group {
        border-radius: 30px !important; /* Apply rounding to the entire input group */
        overflow: hidden; /* Ensure no sharp corners for the input group */
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
    .auth-card .auth-link {
        color: #6B73FF;
        display: block;
        text-align: center;
        margin-top: 10px;
        transition: color 0.3s ease;
    }
    .auth-card .auth-link:hover {
        color: #000DFF;
    }
    .alert-danger {
        border-radius: 30px;
        padding: 10px 20px;
        text-align: center;
        font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="auth-card">
    <h4>Welcome Champ!</h4>
    <h6 class="font-weight-light text-center">Happy to see you!</h6>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <div class="form-group">
        <label for="exampleInputEmail">Username</label>
        <div class="input-group">
          <!-- Removed the icon span element -->
          <input type="text" class="form-control form-control-lg border-left-0" id="exampleInputEmail" name="username" placeholder="Username" required>
        </div>
      </div>
      <div class="form-group">
        <label for="exampleInputPassword">Password</label>
        <div class="input-group">
          <!-- Removed the icon span element -->
          <input type="password" class="form-control form-control-lg border-left-0" id="exampleInputPassword" name="password" placeholder="Password" required>
        </div>
      </div>
      <div class="my-2 d-flex justify-content-between align-items-center">
        <div class="form-check">
          
        </div>
        <a href="reset_password.php" class="auth-link">Reset password</a>
      </div>
      <div class="my-3">
        <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">LOGIN</button>
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

