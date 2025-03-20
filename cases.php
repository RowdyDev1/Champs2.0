<?php
// Include your database connection
include 'db.php';

// Define headers at the top to avoid undefined variable error
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

// Get the account name from the URL
$account_name = isset($_GET['accountName']) ? $_GET['accountName'] : '';

// Check if the account name is provided
if (empty($account_name)) {
    echo "Error: Account name is required in the URL as ?accountName=YOUR_ACCOUNT";
    exit;
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

// Modify SQL Query to filter by Case Created Month
$sql = "
    SELECT * 
    FROM cases1 
    WHERE `Case Account Name` = ? 
    AND `Case Created Month` IN ($monthsList)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query Error: " . $conn->error);
}

$stmt->bind_param('s', $account_name);
$stmt->execute();
$result = $stmt->get_result();


?>


<!DOCTYPE html>
<html>
<head>
    <title>Cases for <?php echo htmlspecialchars($account_name); ?></title>
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
</head>
<body>
    <div class="col-md-12 adjust-position">
        <div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="card-title">Cases for <?php echo htmlspecialchars($account_name); ?></h4>
    <a href="export_cases.php?accountName=<?php echo urlencode($account_name); ?>" class="btn btn-success">
        Export to CSV
    </a>
</div>

            <div class="card-body pb-0" style="height: 1000px; overflow-y: auto;">
                <div class="table-responsive">
                    <table id="multi-filter-select" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <?php foreach ($headers as $header): ?>
                                    <th><?php echo htmlspecialchars($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <?php foreach ($headers as $header): ?>
                                    <th><?php echo htmlspecialchars($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($case = $result->fetch_assoc()): ?>
                                    <tr>
                                        <?php foreach ($headers as $key): ?>
                                            <td><?php echo htmlspecialchars($case[$key] ?? 'N/A'); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo count($headers); ?>">No results found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script>
        $(document).ready(function () {
            $("#multi-filter-select").DataTable({
                pageLength: 5,
                initComplete: function () {
                    this.api()
                        .columns()
                        .every(function () {
                            var column = this;
                            var select = $('<select class="form-select"><option value=""></option></select>')
                                .appendTo($(column.footer()).empty())
                                .on("change", function () {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? "^" + val + "$" : "", true, false).draw();
                                });
                            column.data().unique().sort().each(function (d, j) {
                                select.append('<option value="' + d + '">' + d + "</option>");
                            });
                        });
                },
            });
        });
    </script>
</body>
</html>
