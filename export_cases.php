<?php
// Include database connection
include 'db.php';

// Get the account name from the URL
$account_name = isset($_GET['accountName']) ? $_GET['accountName'] : '';

// Check if account name is provided
if (empty($account_name)) {
    die("Error: Account name is required.");
}

// Get the current month in YYYY-Month format
$currentMonth = date('Y-F');

// Generate last 12 months including the current month
$months = [];
for ($i = 0; $i < 12; $i++) {
    $months[] = date('Y-F', strtotime("-$i months"));
}

// Convert months array into a string for SQL
$monthsList = "'" . implode("', '", $months) . "'";

// Query to fetch cases for the given account name and last 12 months
$sql = "
    SELECT * 
    FROM cases1 
    WHERE `Case Account Name` = ? 
    AND `Case Created Month` IN ($monthsList)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $account_name);
$stmt->execute();
$result = $stmt->get_result();

// Define headers
$headers = [
    "Case ID", "Case Type", "Case Instance Name", "Case Severity",
    "Case Product Unified Line", "Case Account Name", "Case Created Date",
    "Case Final Release", "Case Close Reason", "Case Close Reason Mapping",
    "Case Close Reason Classification", "Case Outflow Date",
    "Case SLA Achieved Restore Full Date", "Case SLA Restore Time Days",
    "Case SLA Restore Severity", "Case Created Month",
    "Case Outflow Month", "Case SLA Resolution Time Days",
    "Case SLA Achieved Resolution Full Date", "Case SLA Resolution Severity",
    "Resolved With/Without Fix"
];

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="cases_' . $account_name . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write the headers
fputcsv($output, $headers);

// Write data rows
while ($row = $result->fetch_assoc()) {
    $data = [];
    foreach ($headers as $header) {
        $data[] = $row[$header] ?? 'N/A';
    }
    fputcsv($output, $data);
}

// Close file
fclose($output);
exit();
?>
