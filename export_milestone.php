<?php
// Include database connection
include 'db.php';

// Set headers for CSV file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=milestone_data.csv');

// Open the output stream
$output = fopen('php://output', 'w');

// Write the CSV column headers
fputcsv($output, [
    'Effective From', 'MS Name', 'Account Name', 'Account Release', 
    'Product Line', 'MS Sub Type', 'From PB', 'To PB', 'SAM'
]);

// Fetch milestone data
$sql = "SELECT EffectiveFrom, MilestoneName, AccountName, AccountRelease, 
               ProductLine, MilestoneSubType, FromPB, ToPB, SupportAccountManager 
        FROM milestonereport";

$result = $conn->query($sql);

// Write each row to the CSV file
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

// Close output stream
fclose($output);
exit;
?>
