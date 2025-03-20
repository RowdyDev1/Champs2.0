<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Graphical Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: linear-gradient(135deg, #e0eaff, #f3e9ff); /* Softer gradient colors */
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 0;
        height: 100vh; /* Full viewport height */
    }

    .container {
        margin-top: 20px; /* Space from the top */
        padding: 0;
        height: 100%; /* Make container full height */
        overflow: hidden; /* Prevent scrolling in container */
    }

    h2 {
        font-weight: bold;
        color: #343a40;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        text-align: center;
        margin-bottom: 20px;
    }

    .table-responsive {
        background: #ffffff;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        overflow: auto; /* Enable both vertical and horizontal scrolling */
        width: 100%; /* Full width */
        max-height: 80vh; /* Set max height to allow vertical scrolling */
        height: auto;
    }

    /* Horizontal and vertical scrolling */
    .table {
        width: 100%; /* Ensure the table takes up the full width */
        border-collapse: collapse;
        table-layout: auto; /* Ensures the table takes up full width and columns are resizable */
    }

    .table th {
        background-color: #5e81ac;
        color: #ffffff;
        font-weight: bold;
        text-transform: uppercase;
        padding: 15px;
        animation: slideDown 0.6s ease-in-out;
        word-wrap: break-word;
    }

    /* Remove hover effect */
    .table tbody tr:hover {
        background-color: transparent; /* Remove background change on hover */
        transform: none; /* Remove scaling effect */
    }

    .table td {
        padding: 12px 15px;
        text-align: center;
        font-size: 1rem;
        word-wrap: break-word;
    }

    .table td a {
        color: #5e81ac;
        font-weight: bold;
        text-decoration: none;
        transition: color 0.3s ease, transform 0.2s ease;
    }

    .table td a:hover {
        color: #4c6ef5;
        transform: translateY(-2px);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>




</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">CM Line Latest HF Release Notes Links</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover text-center">
            <thead>
                <tr>
                    <?php
                    // Database connection
                    $servername = "localhost";
                    $username = "root";
                    $password = "AARRU#champs";
                    $dbname = "accountsinfo";

                    $conn = new mysqli($servername, $username, $password, $dbname);

                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // Fetch unique column values
                    $columnsResult = $conn->query("SELECT DISTINCT Columns FROM cm");
                    $columns = [];
                    while ($col = $columnsResult->fetch_assoc()) {
                        $columns[] = $col['Columns'];
                        echo "<th>" . htmlspecialchars($col['Columns']) . "</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch data and group by column
                $data = [];
                foreach ($columns as $column) {
                    $sql = "SELECT Title, Link FROM cm WHERE Columns='$column'";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $data[$column][] = [
                            'Title' => htmlspecialchars($row['Title']),
                            'Link' => htmlspecialchars($row['Link'])
                        ];
                    }
                }

                // Determine the maximum number of rows needed
                $maxRows = max(array_map('count', $data));

                for ($i = 0; $i < $maxRows; $i++) {
                    echo "<tr>";
                    foreach ($columns as $column) {
                        echo "<td>";
                        if (isset($data[$column][$i])) {
                            echo "<a href='" . $data[$column][$i]['Link'] . "' target='_blank'>" . $data[$column][$i]['Title'] . "</a>";
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                    }
                    echo "</tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
