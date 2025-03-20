<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include 'db.php';
include 'auth.php';
include 'db_config.php';

// Get the account name from the URL
$account_name = isset($_GET['name']) ? $_GET['name'] : '';

// Initialize the array to store the installed products
$installed_products = [];

// Query to fetch data from the installedproduct table
if (!empty($account_name)) {
    // Prepare and execute the SQL query for Installed Products
    $stmt = $conn->prepare("SELECT Product, Version FROM installedproduct WHERE AccountName = ?");
    $stmt->bind_param("s", $account_name);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all installed products data
    while ($row = $result->fetch_assoc()) {
        $installed_products[] = $row;
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installed Products</title>
</head>
<body>
					<table id="multi-filter-select" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Version</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Product</th>
                                <th>Version</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php if (!empty($installed_products)): ?>
                                <?php foreach ($installed_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['Product']); ?></td>
                                    <td><?php echo htmlspecialchars($product['Version']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan='2'>No results found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
					
					    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
     <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>


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
</body>
</html>
