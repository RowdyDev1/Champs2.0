<?php
session_start();
include 'db_config.php'; // Ensure this file contains the correct DB connection settings

// Initialize variables
$id = '';
$requestedRC = '';
$approvedRC = '';
$approvedTill = '';
$approvedBy = '';
$comment = '';
$accountName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountName = $_POST['account_name'];
    $id = $_POST['id']; // Get the ID from the POST data

    // Prepare and execute the SQL query to fetch existing data
    $stmt = $conn->prepare("SELECT id, FINANCIALYEAR, REQUESTEDRC, APPROVEDRC, APPROVEDTILL, APPROVEDBY, COMMENT FROM extensionsupport WHERE id = ? AND AccountName = ?");
    $stmt->bind_param("is", $id, $accountName);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the existing data
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $financialYear = $row['FINANCIALYEAR'];
        $requestedRC = $row['REQUESTEDRC'];
        $approvedRC = $row['APPROVEDRC'];
        $approvedTill = $row['APPROVEDTILL'];
        $approvedBy = $row['APPROVEDBY'];
        $comment = $row['COMMENT'];
    } else {
        // Initialize variables if no data is found
        $financialYear = '';
        $requestedRC = '';
        $approvedRC = '';
        $approvedTill = '';
        $approvedBy = '';
        $comment = '';
    }

    // Close the statement
    $stmt->close();


if (isset($_POST['update_support'])) {
    $newFinancialYear = $_POST['financial_year'];
    $newRequestedRC = $_POST['requested_rc'];
    $newApprovedRC = $_POST['approved_rc'];
    $newApprovedTill = $_POST['approved_till'];
    $newApprovedBy = $_POST['approved_by'];
    $newComment = $_POST['comment'];
    $id = $_POST['id']; // Get the ID

    // Prepare the update statement
    $update_stmt = $conn->prepare("UPDATE extensionsupport SET FINANCIALYEAR = ?, REQUESTEDRC = ?, APPROVEDRC = ?, APPROVEDTILL = ?, APPROVEDBY = ?, COMMENT = ? WHERE id = ?");
    $update_stmt->bind_param("ssssssi", $newFinancialYear, $newRequestedRC, $newApprovedRC, $newApprovedTill, $newApprovedBy, $newComment, $id);
    $update_stmt->execute();
    $update_stmt->close();

    // Redirect back to the main page after the update
    header("Location: index.php?name=" . urlencode($accountName));
    exit();
    $update_stmt->close();
}

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Extension Support Information</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        function clearFields() {
            document.getElementById('financial_year_display').value = '-';
            document.getElementById('requested_rc').value = '-';
            document.getElementById('approved_rc').value = '-';
            document.getElementById('approved_till').value = '-';
            document.getElementById('approved_by').value = '-';
            document.getElementById('comment').value = '-';
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Update Extension Support Information</h1>

    <form method="POST" action="update_extension_support.php">
        <input type="hidden" name="account_name" value="<?php echo htmlspecialchars($accountName); ?>">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>"> <!-- Hidden ID field -->

        <div class="form-group">
            <label for="financial_year_display">Financial Year:</label>
            <input type="text" id="financial_year_display" name="financial_year" class="form-control" value="<?php echo htmlspecialchars($financialYear); ?>" required>
        </div>
        <div class="form-group">
            <label for="requested_rc">Requested RC:</label>
            <input type="text" id="requested_rc" name="requested_rc" class="form-control" value="<?php echo htmlspecialchars($requestedRC); ?>" required>
        </div>
        <div class="form-group">
            <label for="approved_rc">Approved RC:</label>
            <input type="text" id="approved_rc" name="approved_rc" class="form-control" value="<?php echo htmlspecialchars($approvedRC); ?>" required>
        </div>
        <div class="form-group">
            <label for="approved_till">Approved Till:</label>
            <input type="text" id="approved_till" name="approved_till" class="form-control" value="<?php echo htmlspecialchars($approvedTill); ?>" required>
        </div>
        <div class="form-group">
            <label for="approved_by">Approved By:</label>
            <input type="text" id="approved_by" name="approved_by" class="form-control" value="<?php echo htmlspecialchars($approvedBy); ?>" required>
        </div>
        <div class="form-group">
            <label for="comment">Comment:</label>
            <input type="text" id="comment" name="comment" class="form-control" value="<?php echo htmlspecialchars($comment); ?>" required>
        </div>
        <button type="submit" name="update_support" class="btn btn-primary">Update</button>
        <button type="button" onclick="clearFields()" class="btn btn-secondary">Clear</button>
        <a href="index.php?name=<?php echo urlencode($accountName); ?>" class="btn btn-danger">Cancel</a>
    </form>
</div>


</body>
</html>
