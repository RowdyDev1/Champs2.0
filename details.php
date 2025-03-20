<?php
// Fetch URL parameters
$month = isset($_GET['month']) ? urldecode($_GET['month']) : null;
$accountName = isset($_GET['accountName']) ? urldecode($_GET['accountName']) : null;
$severity = isset($_GET['severity']) ? urldecode($_GET['severity']) : null;

if ($month && $accountName && $severity) {
    // Include database connection
    include('db.php');

    // Check if the connection is successful
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // SQL query to fetch data from cases1 table based on parameters
    $sql = "SELECT * FROM cases1 WHERE `Case Created Month` = ? AND `Case Account Name` = ? AND `Case Severity` = ?";

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("SQL error: " . $conn->error); // This will print the MySQL error if the query preparation fails
    }

    // Bind the parameters
    $stmt->bind_param("sss", $month, $accountName, $severity); // Bind parameters for `Case Created Month`, `Case Account Name`, and `Case Severity`

    // Execute the statement
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize an empty array to store fetched data
    $casesData = [];

    if ($result->num_rows > 0) {
        // Fetch data and store in array
        while ($row = $result->fetch_assoc()) {
            $casesData[] = $row;
        }
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid parameters.";
}
?>

<!-- CSS Files -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/css/plugins.min.css" />
<link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

<!-- CSS Just for demo purpose, don't include it in your project -->
<link rel="stylesheet" href="assets/css/demo.css" />

<div class="col-md-12 adjust-position">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Case Details</h4>
        </div>
        <div class="card-body pb-0" style="height: 800px; overflow-y: auto;">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="multi-filter-select" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Type</th>
                                <th>Case Instance Name</th>
                                <th>Case Severity</th>
                                <th>Case Product Unified Line</th>
                                <th>Case Account Name</th>
                                <th>Case Created Date</th>
                                <th>Case Final Release</th>
                                <th>Case Close Reason</th>
                                <th>Case Close Reason Mapping</th>
                                <th>Case Close Reason Classification</th>
                                <th>Case Outflow Date</th>
                                <th>Case SLA Achieved Restore Full Date</th>
                                <th>Case SLA Restore Time Days</th>
                                <th>Case SLA Restore Severity</th>
                                <th>Case Created Month</th>
                                <th>Case Outflow Month</th>
                                <th>Case SLA Resolution Time Days</th>
                                <th>Case SLA Resolution Severity</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Type</th>
                                <th>Case Instance Name</th>
                                <th>Case Severity</th>
                                <th>Case Product Unified Line</th>
                                <th>Case Account Name</th>
                                <th>Case Created Date</th>
                                <th>Case Final Release</th>
                                <th>Case Close Reason</th>
                                <th>Case Close Reason Mapping</th>
                                <th>Case Close Reason Classification</th>
                                <th>Case Outflow Date</th>
                                <th>Case SLA Achieved Restore Full Date</th>
                                <th>Case SLA Restore Time Days</th>
                                <th>Case SLA Restore Severity</th>
                                <th>Case Created Month</th>
                                <th>Case Outflow Month</th>
                                <th>Case SLA Resolution Time Days</th>
                                <th>Case SLA Resolution Severity</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php if (!empty($casesData)): ?>
                                <?php foreach ($casesData as $case): ?>
								<tr>
									<td><?php echo htmlspecialchars($case['Case ID']); ?></td>
									<td><?php echo htmlspecialchars($case['Case Type']); ?></td>
									<td><?php echo htmlspecialchars($case['Case Instance Name']); ?></td>
									<td><?php echo htmlspecialchars($case['Case Severity']); ?></td>
									<td><?php echo htmlspecialchars($case['Case Product Unified Line']); ?></td>
									<td><?php echo htmlspecialchars($case['Case Account Name']); ?></td>
									<td><?php echo htmlspecialchars($case['Case Created Date']); ?></td>
									<td><?php echo isset($case['Case Final Release']) ? htmlspecialchars($case['Case Final Release']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case Close Reason']) ? htmlspecialchars($case['Case Close Reason']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case Close Reason Mapping']) ? htmlspecialchars($case['Case Close Reason Mapping']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case Close Reason Classification']) ? htmlspecialchars($case['Case Close Reason Classification']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case Outflow Date']) ? htmlspecialchars($case['Case Outflow Date']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case SLA Achieved Restore Full Date']) ? htmlspecialchars($case['Case SLA Achieved Restore Full Date']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case SLA Restore Time Days']) ? htmlspecialchars($case['Case SLA Restore Time Days']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case SLA Restore Severity']) ? htmlspecialchars($case['Case SLA Restore Severity']) : 'N/A'; ?></td>
									<td><?php echo htmlspecialchars($case['Case Created Month']); ?></td>
									<td><?php echo htmlspecialchars($case['Case Outflow Month']); ?></td>
									<td><?php echo isset($case['Case SLA Resolution Time Days']) ? htmlspecialchars($case['Case SLA Resolution Time Days']) : 'N/A'; ?></td>
									<td><?php echo isset($case['Case SLA Resolution Severity']) ? htmlspecialchars($case['Case SLA Resolution Severity']) : 'N/A'; ?></td>
								</tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan='18'>No results found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>




<!--   Core JS Files   -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>

<!-- jQuery Scrollbar -->
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

<!-- Chart JS -->
<script src="assets/js/plugin/chart.js/chart.min.js"></script>

<!-- jQuery Sparkline -->
<script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

<!-- Chart Circle -->
<script src="assets/js/plugin/chart-circle/circles.min.js"></script>

<!-- Datatables -->
<script src="assets/js/plugin/datatables/datatables.min.js"></script>

<!-- jQuery Vector Maps -->
<script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
<script src="assets/js/plugin/jsvectormap/world.js"></script>

<!-- Sweet Alert -->
<script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

<!-- Kaiadmin JS -->
<script src="assets/js/kaiadmin.min.js"></script>

<!-- Kaiadmin DEMO methods, don't include it in your project! -->
<script src="assets/js/setting-demo.js"></script>
<script src="assets/js/demo.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  $(document).ready(function () {
    $("#multi-filter-select").DataTable({
      pageLength: 5,
      initComplete: function () {
        this.api()
          .columns()
          .every(function () {
            var column = this;
            var select = $(
              '<select class="form-select"><option value=""></option></select>'
            )
              .appendTo($(column.footer()).empty())
              .on("change", function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());

                column
                  .search(val ? "^" + val + "$" : "", true, false)
                  .draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                select.append(
                  '<option value="' + d + '">' + d + "</option>"
                );
              });
          });
      },
    });
  });
</script>
