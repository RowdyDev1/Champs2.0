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

// Initialize error message
$error_message = "";

// Fetch About Information
if (!empty($account_name)) {
    $stmt_about = $conn->prepare("SELECT About FROM about WHERE AccountName = ?");
    if ($stmt_about) {
        $stmt_about->bind_param("s", $account_name);
        $stmt_about->execute();
        $result_about = $stmt_about->get_result();
        $about = $result_about->num_rows > 0 ? $result_about->fetch_assoc()['About'] : "COMING SOON";
        $stmt_about->close();
    } else {
        $error_message .= "Error preparing 'about' query: " . $conn->error . "\n";
    }

    // Fetch Focal Points Information
    $stmt_fp = $conn->prepare("SELECT Name, Type FROM focalpoints WHERE AccountName = ?");
    if ($stmt_fp) {
        $stmt_fp->bind_param("s", $account_name);
        $stmt_fp->execute();
        $result_fp = $stmt_fp->get_result();
        $focal_points = [];
        while ($row = $result_fp->fetch_assoc()) {
            $focal_points[] = $row;
        }
        $stmt_fp->close();
    } else {
        $error_message .= "Error preparing 'focalpoints' query: " . $conn->error . "\n";
    }

    // Fetch the last run date
    $query = "SELECT Last_Run FROM `lastrun` ORDER BY Last_Run DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    $lastRunDate = null;
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $lastRunDate = $row['Last_Run'];
    }

    // Fetch Agreement Information
    $stmt_agreement = $conn->prepare("SELECT Agreement, FOPs, StartDate, EndDate FROM agreement WHERE AccountName = ?");
    if ($stmt_agreement) {
        $stmt_agreement->bind_param("s", $account_name);
        $stmt_agreement->execute();
        $result_agreement = $stmt_agreement->get_result();
        $agreements = [];
        while ($row = $result_agreement->fetch_assoc()) {
            $agreements[] = $row;
        }
        $stmt_agreement->close();
    }

    // Fetch Extension Support Information
    $extension_support = [];
    $stmt = $conn->prepare("SELECT id, FINANCIALYEAR, REQUESTEDRC, APPROVEDRC, APPROVEDTILL, APPROVEDBY, COMMENT, Last_Updated FROM extensionsupport WHERE AccountName = ?");
    if ($stmt) {
        $stmt->bind_param("s", $account_name);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $extension_support[] = $row;
        }
        $stmt->close();
    }

    // Fetch Installed Products
    $installed_products = [];
    $stmt = $conn->prepare("SELECT Product, Version FROM installedproduct WHERE AccountName = ?");
    if ($stmt) {
        $stmt->bind_param("s", $account_name);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $installed_products[] = $row;
        }
        $stmt->close();
    }

    // Fetch Account Name for Display
    $display_name = "Invalid account name.";
    $stmt = $conn->prepare("SELECT AccountName FROM accountname WHERE AccountName = ?");
    if ($stmt) {
        $stmt->bind_param("s", $account_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $display_name = $row['AccountName'];
        }
        $stmt->close();
    }

    // Fetch SAM Name
    $sam_name = "Invalid account name.";
    $stmt = $conn->prepare("SELECT sam_name FROM sam WHERE AccountName = ?");
    if ($stmt) {
        $stmt->bind_param("s", $account_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $sam_name = $row['sam_name'];
        } else {
            $sam_name = "No SAM";
        }
        $stmt->close();
    }

    // Fetch Region Information
    $region = "Not Available";
    $stmt = $conn->prepare("SELECT region FROM region WHERE AccountName = ?");
    if ($stmt) {
        $stmt->bind_param("s", $account_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $region = $row['region'];
        }
        $stmt->close();
    }

    // Fetch Country Information
    $country = "Not Available";
    $stmt = $conn->prepare("SELECT country FROM country WHERE AccountName = ?");
    if ($stmt) {
        $stmt->bind_param("s", $account_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $country = $row['country'];
        }
        $stmt->close();
    }

$information_list = [];
$last_update = "";
$access_users = []; // Initialize empty array

// Fetch Important Information
$stmt = $conn->prepare("SELECT information, updated_at, access FROM importantinformation WHERE AccountName = ?");
if ($stmt) {
    $stmt->bind_param("s", $account_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $information_list[] = $row;
        $last_update = $row['updated_at'];

        // Ensure access column is properly handled
        if (!empty($row['access'])) {
            $access_users = array_map('strtolower', explode(',', $row['access'])); // Case-insensitive list
        }
    }
    $stmt->close();
}

// Convert session username to lowercase for case-insensitive comparison
$current_user = strtolower($_SESSION['username'] ?? '');

// Check user access (Case Insensitive)
$can_edit = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor' || in_array($current_user, $access_users));



// Initialize variable to hold fetched data
$milestone_data = [];

if (!empty($account_name)) {
    // Prepare and execute the SQL query for Extension Support
    $stmt = $conn->prepare("SELECT EFFECTIVEFROM, MILESTONENAME, ACCOUNTNAME, ACCOUNTRELEASE, PRODUCTLINE, MILESTONESUBTYPE, FROMPB, TOPB, SUPPORTACCOUNTMANAGER FROM milestonereport WHERE AccountName = ?");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    
    $stmt->bind_param("s", $account_name);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all extension support data
    while ($row = $result->fetch_assoc()) {
        $milestone_data[] = $row;
    }

    // Close the statement
    $stmt->close();
}

// Initialize variable to hold fetched data
$ahtData = [];

if (!empty($account_name)) {
    // Get the current year
    $currentYear = date('Y');
    $previousYear = $currentYear - 1;

    // Prepare the query to fetch AHT data from the 'aht' table
    $stmt = $conn->prepare("
        SELECT Month, Severity, AHT 
        FROM aht
        WHERE AccountName = ? AND (Month LIKE ? OR Month LIKE ?)
    ");

    // Fetch all months for the current year and the last two months of the previous year
    $yearMonthPatternCurrent = "$currentYear-%";
    $yearMonthPatternPrevious = "$previousYear-%";
    $stmt->bind_param('sss', $account_name, $yearMonthPatternCurrent, $yearMonthPatternPrevious);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all AHT data
    while ($row = $result->fetch_assoc()) {
        $ahtData[] = $row;
    }

    // Close the statement
    $stmt->close();
}
// Initialize variable to hold fetched data
$caseData = [];

if (!empty($account_name)) {
    // Get the current year
$currentYear = date('Y');
$previousYear = $currentYear - 1;

$stmt = $conn->prepare("
    SELECT Month, Severity, Count 
    FROM in_flow
    WHERE AccountName = ? AND (Month LIKE ? OR Month LIKE ?)
");

// Fetch all months for the current year and the last two months of the previous year
$yearMonthPatternCurrent = "$currentYear-%";
$yearMonthPatternPrevious = "$previousYear-%";
$stmt->bind_param('sss', $account_name, $yearMonthPatternCurrent, $yearMonthPatternPrevious);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();


    // Fetch all case data
    while ($row = $result->fetch_assoc()) {
        $caseData[] = $row;
    }

    // Close the statement
    $stmt->close();
}


// Initialize variable to hold fetched data for Out_flow
$caseData1 = [];

if (!empty($account_name)) {
    $currentYear = date('Y');
    $previousYear = $currentYear - 1;

    $stmt = $conn->prepare("
        SELECT Month, Severity, Count 
        FROM out_flow
        WHERE AccountName = ? AND (Month LIKE ? OR Month LIKE ?)
    ");
    $yearMonthPatternCurrent = "$currentYear-%";
    $yearMonthPatternPrevious = "$previousYear-%";
    $stmt->bind_param('sss', $account_name, $yearMonthPatternCurrent, $yearMonthPatternPrevious);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $caseData1[] = $row;
    }

    $stmt->close();
}
// Fetch Fix Data for Last 12 Months
$fixData = [];
$lastTwelveMonths = [];
for ($i = 0; $i < 12; $i++) {
    $month = date('F', strtotime("-$i month"));
    $year = date('Y', strtotime("-$i month"));
    $lastTwelveMonths[] = "$year-$month";
}
$lastTwelveMonths = array_reverse($lastTwelveMonths);
$placeholders = implode(',', array_fill(0, count($lastTwelveMonths), '?'));
$stmt = $conn->prepare("SELECT Month, 
                               SUM(Handling) AS Handling, 
                               SUM(Resolved_Without_FIx) AS Resolved_Without_FIx, 
                               SUM(New_Code_Fix_Provided) AS New_Code_Fix_Provided, 
                               SUM(Existing_Code_Fix_Provided) AS Existing_Code_Fix_Provided
                        FROM Fix_No_Fix
                        WHERE AccountName = ? AND Month IN ($placeholders)
                        GROUP BY Month");
$stmt->bind_param(str_repeat('s', count($lastTwelveMonths) + 1), $account_name, ...$lastTwelveMonths);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $fixData[] = [
        'Month' => $row['Month'],
        'Handling' => $row['Handling'] ?? 0,
        'Resolved_Without_FIx' => $row['Resolved_Without_FIx'] ?? 0,
        'New_Code_Fix_Provided' => $row['New_Code_Fix_Provided'] ?? 0,
        'Existing_Code_Fix_Provided' => $row['Existing_Code_Fix_Provided'] ?? 0
    ];
}
$stmt->close();

// Fetch Summary
$summary = "No summary available";
$sql = "SELECT Summary FROM casesummary WHERE AccountName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $account_name);
$stmt->execute();
$result = $stmt->get_result();
$summary = $result->fetch_assoc()['Summary'] ?? 'No summary available';
$stmt->close();

// Close the connection
$conn->close();
}

?>


<script>
    var caseData = <?php echo json_encode($caseData); ?>;
</script>



<script>
    // Pass PHP data to JavaScript
    var fixData = <?php echo json_encode($fixData); ?>;
</script>

<script>
// PHP array to JavaScript
var caseata = <?php echo json_encode($data); ?>;
var labels = <?php echo json_encode($months); ?>;
</script>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Champs</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" type="image/x-icon" href="/UAT/android-chrome-512x512.png">

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons"
                ],
                urls: ["assets/css/fonts.min.css"]
            },
            active: function () {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <!-- Consolidated CSS -->
    <style>
        /* Navbar Styles */
        .navbar {
            background-color: black !important;
        }

        /* Logout Button */
        .logout-btn {
            color: #000;
            background-color: #fff;
            border-radius: 4px;
            padding: 8px 12px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #000;
            color: #fff;
        }

        /* Chatbot Styles */
        .chatbot-container {
            position: fixed;
            bottom: 0;
            right: 20px;
            width: 500px;
            max-width: 90%;
            height: 600px;
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: #fff;
            z-index: 1050;
        }

        .chatbot-header {
            padding: 10px;
            background: #007bff;
            color: #fff;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            text-align: center;
            font-weight: bold;
        }

        .chatbot-body {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
            max-height: 500px;
            color: #333;
        }

        .chatbot-body::-webkit-scrollbar {
            width: 8px;
        }

        .chatbot-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chatbot-body::-webkit-scrollbar-thumb {
            background: #007bff;
            border-radius: 10px;
        }

        .chatbot-body::-webkit-scrollbar-thumb:hover {
            background: #0056b3;
        }

        .chatbot-footer {
            display: flex;
            align-items: center;
            padding: 10px;
            border-top: 1px solid #ddd;
            background: #fff;
        }

        .chatbot-footer input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }

        .chatbot-footer button {
            padding: 10px 15px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .chatbot-footer button:hover {
            background: #0056b3;
        }

        #chatbot-clear {
            margin-left: 10px;
            padding: 10px 15px;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #chatbot-clear:hover {
            background: #c82333;
        }

        .user-message {
            color: #007bff;
            font-weight: bold;
            text-align: right;
        }

        .chatbot-message {
            color: #28a745;
            font-weight: bold;
            text-align: left;
        }

        /* Popup Styles */
        .popup {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .popup-content {
            position: relative;
            width: 500px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            text-align: center;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
        }

        /* Highlight Section */
        .highlight {
            background-color: #fff;
            color: #ff0000;
            padding: 5px;
            text-align: center;
            border-bottom: 2px solid #fbc02d;
            margin-bottom: 25px;
        }
		.chatbot-message table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 10px;
		}

		.chatbot-message th, .chatbot-message td {
			border: 1px solid #ddd;
			padding: 8px;
			text-align: left;
		}

		.chatbot-message th {
			background-color: #007bff;
			color: white;
		}

    </style>
</head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">

        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
	  <li class="nav-section">
        <span class="sidebar-mini-icon">
          <i class="fa fa-ellipsis-h"></i>
        </span>
        <h4 class="text-section">Navigation</h4>
      </li>

              <li class="nav-item active">
                <a href="dashboard.php">
                  <i class="fas fa-money-check"></i>
                  <p>Home</p>
               
				</a>
              </li>
<li class="nav-item active">
  <a href="http://sciomeil/sites/PBG-Oncall-Support-Info/Pages/On-Call-Manager.aspx" " target="_blank">
    <i class="fas fa-headphones"></i>
    <p>Oncall Page</p>
  </a>
</li>
<li class="nav-item active">
  <a href="https://confluence/display/AC/FY25+CES+Consolidate+Cadence" " target="_blank">
    <i class="icon-graph"></i>
    <p>CES Consolidate Cadence</p>
  </a>
</li>
  <li class="nav-item active">
  <a href="https://amdocs.sharepoint.com/sites/APS-Portal/SitePages/CSO-SAMsFP.aspx" " target="_blank">
    <i class="icon-briefcase"></i>
    <p>SAM Page</p>
  </a>
</li>
<li class="nav-item active">
  <a href="https://www.amdocs-support.com/nav_to.do" " target="_blank">
    <i class="fas fa-user"></i>
    <p>SupportOne</p>
  </a>
</li>
<li class="nav-item active">
  <a href="https://confluence/display/APSD/Account+adoption+calander" target="_blank">
    <i class="fas fa-calendar-check"></i>
    <p>APS Adoption Calendar</p>
  </a>
</li>
<li class="nav-item active">
  <a href="https://confluence/display/APSL1/CHAMPS+Application+User+Interface+Guide" target="_blank">
    <i class="fas fa-box"></i>
    <p>User Guide</p>
  </a>
</li>
<li class="nav-item active">
  <a href="https://confluence/pages/viewpage.action?pageId=213978054" target="_blank">
    <i class="fas fa-box"></i>
    <p>EOS</p>
  </a>
</li>


<li class="nav-item active">
  <a href="About\AboutL1.html" target="_blank">
    <i class="fas fa-address-book"></i>
    <p>About</p>
  </a>
</li>

			                
      <li class="nav-section">
        <span class="sidebar-mini-icon">
          <i class="fa fa-ellipsis-h"></i>
        </span>
        <h4 class="text-section">List</h4>
      </li>
<li class="nav-item active">
  <a data-bs-toggle="collapse" href="#base">
    <i class="fas fa-th-list"></i>
    <p>Accounts (SAM)</p>
    <span class="caret"></span>
  </a>
  <div class="collapse" id="base">
    <ul class="nav nav-collapse">

     <li>
        <a href="index.php?name=A1%20Austria">
            <span class="sub-item">A1 Austria</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=A1%20Bulgaria">
            <span class="sub-item">A1 Bulgaria</span>
        </a>
    </li>


    <li>
        <a href="index.php?name=Altice%20USA">
            <span class="sub-item">Altice USA</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=AT%26T%20Consumer">
            <span class="sub-item">AT&T Consumer</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=AT%26T%20Inc.">
            <span class="sub-item">AT&T Inc.</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=AT%26T%20LATAM">
            <span class="sub-item">AT&T LATAM</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=AT%26T%20Cricket">
            <span class="sub-item">AT&T Cricket</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Bell%20Canada">
            <span class="sub-item">Bell Canada</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=BT%20Ignite%20Espana">
            <span class="sub-item">BT Ignite Espana</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=BT%20plc">
            <span class="sub-item">BT plc</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Charter%20Communications%20Inc">
            <span class="sub-item">Charter Communications Inc</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Claro%20Brasil">
            <span class="sub-item">Claro Brasil</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Claro%20Dominicana">
            <span class="sub-item">Claro Dominicana</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Claro%20Puerto%20Rico">
            <span class="sub-item">Claro Puerto Rico</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Comcast%20Corporation">
            <span class="sub-item">Comcast Corporation</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Crnogorski%20Telekom%20AD">
            <span class="sub-item">Crnogorski Telekom AD</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Delta%20Lloyd%20Life%20Belgium">
            <span class="sub-item">Delta Lloyd Life Belgium</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Everything%20Everywhere%20Ltd.">
            <span class="sub-item">Everything Everywhere Ltd.</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Far%20EasTone">
            <span class="sub-item">Far EasTone</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Globe%20Telecom">
            <span class="sub-item">Globe Telecom</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Hutchison%203G%20Ireland">
            <span class="sub-item">Hutchison 3G Ireland</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Hutchison%203G%20UK">
            <span class="sub-item">Hutchison 3G UK</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=J:COM%20(Jupiter%20Telecom)">
            <span class="sub-item">J:COM (Jupiter Telecom)</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Kcell">
            <span class="sub-item">Kcell</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=KT%20Corp">
            <span class="sub-item">KT Corp</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=LG%20Uplus%20Corp.">
            <span class="sub-item">LG Uplus Corp.</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Maxis%20Communications">
            <span class="sub-item">Maxis Communications</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Optus">
            <span class="sub-item">Optus</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Philippine%20Long%20Distance%20Telephone%20Company%20(PLDT)">
            <span class="sub-item">Philippine Long Distance Telephone Company (PLDT)</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Rogers%20Wireless%20Inc">
            <span class="sub-item">Rogers Wireless Inc</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=SFR%20(Societe%20Francaise%20de%20Radiotelephone)">
            <span class="sub-item">SFR (Societe Francaise de Radiotelephone)</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=SingTel">
            <span class="sub-item">SingTel</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Sprint">
            <span class="sub-item">Sprint</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=T-Mobile%20Czech%20Republic">
            <span class="sub-item">T-Mobile Czech Republic</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=T-Mobile%20Netherlands">
            <span class="sub-item">T-Mobile Netherlands</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=T-Mobile%20USA">
            <span class="sub-item">T-Mobile USA</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Telefonica%20Brazil">
            <span class="sub-item">Telefonica Brazil</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Telefonica%20de%20Argentina">
            <span class="sub-item">Telefonica de Argentina</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Telefonica%20Moviles%20Chile%20(Movistar)">
            <span class="sub-item">Telefonica Moviles Chile (Movistar)</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Telkom%20SA%20Ltd.">
            <span class="sub-item">Telkom SA Ltd.</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Telkomsel">
            <span class="sub-item">Telkomsel</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Telenor%20Hungary">
            <span class="sub-item">Telenor Hungary</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=The%20Standard%20Bank%20of%20South%20Africa%20Limited">
            <span class="sub-item">The Standard Bank of South Africa Limited</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=True%20Move">
            <span class="sub-item">True Move</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=US%20Cellular%20Corporation">
            <span class="sub-item">US Cellular Corporation</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Veon">
            <span class="sub-item">Veon</span>
        </a>
    </li>

    <li>
        <a href="index.php?name=Vodafone%20D2%20GmbH">
            <span class="sub-item">Vodafone D2 GmbH</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20Group%20plc">
            <span class="sub-item">Vodafone Group plc</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20Hungary">
            <span class="sub-item">Vodafone Hungary</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20India%20Limited">
            <span class="sub-item">Vodafone India Limited</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20Ireland">
            <span class="sub-item">Vodafone Ireland</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20Netherlands">
            <span class="sub-item">Vodafone Netherlands</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20Portugal">
            <span class="sub-item">Vodafone Portugal</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20Romania">
            <span class="sub-item">Vodafone Romania</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20UK%20(Fixed)">
            <span class="sub-item">Vodafone UK (Fixed)</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Vodafone%20UK%20(Mobile)">
            <span class="sub-item">Vodafone UK (Mobile)</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=XL%20Axiata">
            <span class="sub-item">XL Axiata</span>
        </a>
    </li>
    <li>
        <a href="index.php?name=Ziggo">
            <span class="sub-item">Ziggo</span>
        </a>
    </li>

    </ul>
  </div>
</li>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

      <div class="main-panel">
        <div class="main-header">
          <div class="main-header-logo">
            <!-- Logo Header -->
            <div class="logo-header" data-background-color="dark">
              <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                  <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                  <i class="gg-menu-left"></i>
                </button>
              </div>
              <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
              </button>
            </div>
            <!-- End Logo Header -->
          </div>
<!-- Navbar Header -->
	<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
	  <div class="container-fluid">
	<li class="nav-item d-flex align-items-center">
    <span class="nav-link text-white me-3">
        Last Updated: <?php echo htmlspecialchars($lastRunDate); ?>
    </span>
</li>

    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
	    <!-- Navbar with Bell Icon -->
		
<li class="nav-item active">
    <a href="https://confluence/display/APSL1/CHAMPS+Application+User+Interface+Guide" target="_blank">
        <i class="fa fa-question-circle fa-2x" style="color: #0d6efd;"></i> 
    </a>
</li>




                <li class="nav-item topbar-icon dropdown hidden-caret">
                  <a
                    class="nav-link dropdown-toggle"
                    href="#"
                    id="notifDropdown"
                    role="button"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                  >
                    <i class="fa fa-bell"></i>
                    <span class="notification">4</span>
                  </a>
                  <ul
                    class="dropdown-menu notif-box animated fadeIn"
                    aria-labelledby="notifDropdown"
                  >
                    <li>
                      <div class="dropdown-title">
                        You have 4 new notification
                      </div>
                    </li>
                    <li>
                      <div class="notif-scroll scrollbar-outer">
                        <div class="notif-center">
                          <a href="#">
                            <div class="notif-icon notif-danger">
                              <i class="fas fa-chevron-right"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block"> Fix No Fix Chart bug is resolved </span>
                              <span class="time">02/03/2025</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-icon notif-danger">
                              <i class="fas fa-chevron-right"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block">
                                Account Wise AHT is added
                              </span>
							  <span class="block">We are working on data sorting</span>
                              <span class="time">02/03/2025</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-icon notif-danger">
                              <i class="fas fa-chevron-right"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block">
                                In-flow and outflow 
                              </span>
							  <span class="block"> merged in to single chart</span>
                              <span class="time">02/04/2025</span>
                            </div>
                          </a>
                          <a href="#">
                            <div class="notif-icon notif-danger">
                              <i class="fas fa-chevron-right"></i>
                            </div>
                            <div class="notif-content">
                              <span class="block"> Product Line Wise Page is Added</span>
							  <span class="time">02/05/2025</span>
                            </div>
                          </a>
                        </div>
                      </div>
                    </li>
                  </ul>
                </li>
<li class="nav-item">
  <button class="btn chatbot-btn" id="chatbot-toggle">
    <i class="fas fa-robot"></i> ChampsGPT
  </button>
</li>

<style>
  .chatbot-btn {
    background: linear-gradient(45deg, #007bff, #6610f2);
    border: none;
    color: #fff;
    font-weight: bold;
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 50px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease-in-out;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: bounce 2s infinite ease-in-out;
  }

  .chatbot-btn i {
    font-size: 18px;
  }

  /* Continuous Bounce Animation */
  @keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
  }

  /* Glow effect on hover */
  .chatbot-btn:hover {
    transform: scale(1.05);
    box-shadow: 0px 6px 15px rgba(0, 123, 255, 0.5);
  }
</style>


                <li class="nav-item topbar-user dropdown hidden-caret">
                  <a
                    class="dropdown-toggle profile-pic"
                    data-bs-toggle="dropdown"
                    href="#"
                    aria-expanded="false"
                  >
                    <div class="avatar-sm">
                      <img
                        src="assets/img/149071.png"
                        alt="..."
                        class="avatar-img rounded-circle"
                      />
                    </div>
				<span class="profile-username" style="color: white;">
					<span class="op-7">Hi,</span>
					<span class="fw-bold"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
				</span>

                  </a>
                  <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <div class="dropdown-user-scroll scrollbar-outer">
                      <li>
                        <div class="user-box">
                          <div class="avatar-lg">
                            <img
                              src="assets/img/149071.png"
                              alt="image profile"
                              class="avatar-img rounded"
                            />
                          </div>
						<div class="u-text">
							<span class="fw-bold"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
							<p class="text-muted"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
						</div>

                        </div>
                      </li>
                      <li>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">Logout</a>
                      </li>
                    </div>
                  </ul>
                </li>
    </ul>
	  </div>
	</nav>
<!-- End Navbar -->


        </div>

        <div class="container">
		<?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($error_message)); ?></div>
        <?php endif; ?>
          <div class="page-inner">
            <div
              class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4"
            >
             <div>
        <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($display_name); ?></h3>
    </div>

            </div>
            <div class="row">
<div class="col-sm-6 col-md-4 custom-width">
    <div class="card card-stats card-primary card-round">
        <div class="card-body">
            <div class="row">
                <div class="col-5">
                    <div class="icon-big text-center">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="col-7 col-stats">
                    <div class="text">
                        <p class="card-title" style="font-size: 15px;">SAM</p>
                        <h2 class="card-title" style="font-size: 15px;"><?php echo htmlspecialchars($sam_name); ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-sm-6 col-md-4 custom-width">
        <div class="card card-stats card-success card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-map"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-title" style="font-size: 15px;">Region</p>
                            <h2 class="card-title" style="font-size: 15px;"><?php echo htmlspecialchars($region); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="col-sm-6 col-md-4 custom-width">
        <div class="card card-stats card-secondary card-round">
            <div class="card-body">
                <div class="row">
                    <div class="col-5">
                        <div class="icon-big text-center">
                            <i class="fas fa-map-marker"></i>
                        </div>
                    </div>
                    <div class="col-7 col-stats">
                        <div class="numbers">
                            <p class="card-title" style="font-size: 15px;">Country</p>
                            <h2 class="card-title" style="font-size: 15px;"><?php echo htmlspecialchars($country); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



			  </div>

			  
<div class="highlight" style="position: relative;">
    <h1>Important Information</h1>
    
    <!-- Display Last Update Time -->
    <?php if (!empty($last_update)): ?>
        <div style="position: absolute; top: 10px; right: 10px; font-size: 12px; color: gray;">
            Last Update: <?php echo htmlspecialchars(date("Y-m-d H:i:s", strtotime($last_update))); ?>
        </div>
    <?php endif; ?>

        <?php foreach ($information_list as $info): ?>
            <p><b><?php echo htmlspecialchars($info['information']); ?></b></p>
        <?php endforeach; ?>

        <!-- Show Add Information button based on role -->
        <?php if ($can_edit): ?>
            <a href="manage_information.php?account_name=<?php echo urlencode($account_name); ?>" class="btn btn-primary">Add Information</a>
        <?php endif; ?>
</div>


<div class="row">

<div class="col-md-12">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">Case Flow</div>
			            <div class="d-flex justify-content-center mb-3">
                <button class="btn btn-outline-primary me-2 active" id="btnInFlow" onclick="showChart('inFlow')">In-Flow</button>
                <button class="btn btn-outline-secondary" id="btnOutFlow" onclick="showChart('outFlow')">Out-Flow</button>
            </div>
            <a href="cases.php?accountName=<?php echo urlencode($account_name); ?>" class="btn btn-primary">View Cases</a>
			
        </div>
        <div class="card-body pb-0" style="max-height: 450px; overflow-y: auto;">

            <div class="chart-container" id="inFlowChartContainer">
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-container" id="outFlowChartContainer" style="display: none;">
                <canvas id="barChart1"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    showChart('inFlow'); // Default to In-Flow
});

function showChart(type) {
    if (type === 'inFlow') {
        document.getElementById('inFlowChartContainer').style.display = 'block';
        document.getElementById('outFlowChartContainer').style.display = 'none';
        document.getElementById('btnInFlow').classList.add('active', 'btn-primary');
        document.getElementById('btnOutFlow').classList.remove('active', 'btn-primary');
        document.getElementById('btnOutFlow').classList.add('btn-outline-secondary');
    } else {
        document.getElementById('inFlowChartContainer').style.display = 'none';
        document.getElementById('outFlowChartContainer').style.display = 'block';
        document.getElementById('btnOutFlow').classList.add('active', 'btn-primary');
        document.getElementById('btnInFlow').classList.remove('active', 'btn-primary');
        document.getElementById('btnInFlow').classList.add('btn-outline-primary');
    }
}
</script>

<style>
.chart-container {
    position: relative;
    height: 350px;
    width: 100%;
}
.btn-outline-primary.active, 
.btn-outline-secondary.active {
    color: #fff;
}
</style>





<div class="col-md-9">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Average Handling Time (In Days)</div>
        </div>
        <div class="card-body pb-0" style="max-height: 500px; overflow-y: auto;">
            <div class="chart-container">
                <canvas id="myLineChart"></canvas>
            </div>
        </div>
    </div>
</div>
<!-- Popup Notification -->
<div id="notificationPopup" class="notification-popup" style="display: none;">
  <div class="popup-content">
    <h4>New Notifications</h4>
    <ul>
      <li>New message from Admin.</li>
      <li>System update available.</li>
      <li>Your profile was updated.</li>
    </ul>
  </div>
</div>




<div class="col-md-3">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Fix No Fix</div>
        </div>
        <div class="card-body pb-0" style="max-height: 370px; overflow-y: auto;">
            <div class="chart-container">
                <canvas id="doughnutChart"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="chatbot-container" id="chatbot">
  <div class="chatbot-header">
      ChampsBOT
    <button id="chatbot-close" class="btn btn-sm btn-light float-end">✖</button>
	<a href="output.txt" download="output.txt">

</a>

  </div>
  <div class="chatbot-body" id="chatbot-body">
    <p>Hi! How can I assist you today?</p>
  </div>
  <div class="chatbot-footer">
    <input type="text" id="chatbot-input" placeholder="Type your message...">
    <button id="chatbot-send">Send</button>
    <button id="chatbot-clear" class="btn btn-danger">Clear</button> <!-- Clear button -->
  </div>
</div>

</div>

		 <div class="row">

    <div class="col-md-6">
        <div class="card card-round">
            <div class="card-header">
                <div class="card-head-row">
                    <div class="card-title">About</div>
                </div>
            </div>
            <div class="card-body" style="height: 550px; overflow-y: auto;">
                <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                        <p><?php echo nl2br(htmlspecialchars($about)); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="col-md-6 adjust-position">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Project Installed Product Line Versions</h4>
        </div>
        <div class="card-body pb-0" style="height: 550px; overflow-y: auto;">
			<div class="card-body">
				<div class="table-responsive">
					<table id="multi-filter-select" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Product Line</th>
                                <th>Version</th>
                            </tr>
                        </thead>
        <tfoot>
            <tr>
                <th>Product Line</th>
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
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-md-12">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Milestone</div>
        </div>
        <div class="card-body" style="max-height: 250px; overflow-y: auto;">
            <table class="table mt-3">
                <thead>
                    <tr>
                        <th scope="col">Effective From</th>
                        <th scope="col">MS Name</th>
                        <th scope="col">Account Name</th>
                        <th scope="col">Account Release</th>
                        <th scope="col">Product Line</th>
                        <th scope="col">MS Sub Type</th>
                        <th scope="col">From PB</th>
                        <th scope="col">To PB</th>
                        <th scope="col">SAM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($milestone_data)): ?>
                        <?php foreach ($milestone_data as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['EFFECTIVEFROM']); ?></td>
                                <td><?php echo htmlspecialchars($data['MILESTONENAME']); ?></td>
                                <td><?php echo htmlspecialchars($data['ACCOUNTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($data['ACCOUNTRELEASE']); ?></td>
                                <td><?php echo htmlspecialchars($data['PRODUCTLINE']); ?></td>
                                <td><?php echo htmlspecialchars($data['MILESTONESUBTYPE']); ?></td>
                                <td><?php echo htmlspecialchars($data['FROMPB']); ?></td> 
                                <td><?php echo htmlspecialchars($data['TOPB']); ?></td>
                                <td><?php echo htmlspecialchars($data['SUPPORTACCOUNTMANAGER']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Extension Support</div>
        </div>
        <div class="card-body" style="max-height: 250px; overflow-y: auto;">
            <table class="table mt-3">
                <thead>
                    <tr>
                        <th scope="col">Financial Year</th>
                        <th scope="col">Requested RC</th>
                        <th scope="col">Approved RC</th>
                        <th scope="col">Approved Till</th>
                        <th scope="col">Approved By</th>
                        <th scope="col">Comment</th>
                        <th scope="col">Last Update</th>
                        <?php if ($can_edit): ?> <!-- Hide Action column if user has no access -->
                            <th scope="col">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($extension_support as $support): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($support['FINANCIALYEAR']); ?></td>
                        <td><?php echo htmlspecialchars($support['REQUESTEDRC']); ?></td>
                        <td><?php echo htmlspecialchars($support['APPROVEDRC']); ?></td>
                        <td><?php echo htmlspecialchars($support['APPROVEDTILL']); ?></td>
                        <td><?php echo htmlspecialchars($support['APPROVEDBY']); ?></td>
                        <td><?php echo htmlspecialchars($support['COMMENT']); ?></td>
                        <td><?php echo htmlspecialchars($support['Last_Updated']); ?></td> 
                        <?php if ($can_edit): ?>
                        <td>
                            <form method="POST" action="update_extension_support.php">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($support['id']); ?>">
                                <input type="hidden" name="account_name" value="<?php echo htmlspecialchars($account_name); ?>">
                                <button type="submit" class="btn btn-warning">Edit</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
      </div>
	</div>
	
<!-- Core JS Files -->
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



<!-- Chart.js and Chart.js Plugin for Data Labels -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Ensure ahtData is only declared once
    const ahtData = <?php echo json_encode($ahtData); ?>;
    console.log("AHT Data:", ahtData);

    // Check if ahtData is available
    if (ahtData && ahtData.length > 0) {
        // Static months from February 2024 to January 2025
        var ahtLabels = [
            "2024-April", "2024-May", "2024-June",
            "2024-July", "2024-August", "2024-September", "2024-October", "2024-November",
            "2024-December", "2025-January", "2025-February", "2025-March"
        ];

        var s1AHT = Array(12).fill(''); // Renamed from 's1Data'
        var s2AHT = Array(12).fill(''); // Renamed from 's2Data'
        var s3AHT = Array(12).fill(''); // Renamed from 's3Data'
        var s4AHT = Array(12).fill(''); // Renamed from 's4Data'

        // Parse AHT data and populate arrays
        ahtData.forEach(function(ahtEntry) {
            var month = ahtEntry.Month;
            var severity = ahtEntry.Severity;
            var aht = parseFloat(ahtEntry.AHT);  // Ensure it's parsed as a number

            var monthIndex = ahtLabels.indexOf(month); // Renamed from 'labels'
            if (monthIndex > -1) {
                switch (severity) {
                    case "S1":
                        s1AHT[monthIndex] = aht > 0 ? aht : ''; // Renamed from 's1Data'
                        break;
                    case "S2":
                        s2AHT[monthIndex] = aht > 0 ? aht : ''; // Renamed from 's2Data'
                        break;
                    case "S3":
                        s3AHT[monthIndex] = aht > 0 ? aht : ''; // Renamed from 's3Data'
                        break;
                    case "S4":
                        s4AHT[monthIndex] = aht > 0 ? aht : ''; // Renamed from 's4Data'
                        break;
                }
            }
        });

        // Check if the canvas exists before attempting to getContext
        var canvas = document.getElementById("myLineChart");
        if (canvas) {
            var lineChart = canvas.getContext("2d");

            var myLineChart = new Chart(lineChart, {
                type: "line",
                data: {
                    labels: ahtLabels, // Renamed from 'labels'
                    datasets: [
                        {
                            label: "S1 AHT",
                            borderColor: "rgb(255, 0, 0)",
                            backgroundColor: "rgba(245, 191, 186)",
                            fill: true,
                            data: s1AHT, // Renamed from 's1Data'
                            tension: 0.4
                        },
                        {
                            label: "S2 AHT",
                            borderColor: "rgb(255, 127, 62)",
                            backgroundColor: "rgba(255, 127, 62, 0.1)",
                            fill: true,
                            data: s2AHT, // Renamed from 's2Data'
                            tension: 0.4
                        },
                        {
                            label: "S3 AHT",
                            borderColor: "rgb(255, 201, 74)",
                            backgroundColor: "rgba(255, 201, 74, 0.1)",
                            fill: true,
                            data: s3AHT, // Renamed from 's3Data'
                            tension: 0.4
                        },
                        {
                            label: "S4 AHT",
                            borderColor: "rgb(6, 208, 1)",
                            backgroundColor: "rgba(6, 208, 1, 0.1)",
                            fill: true,
                            data: s4AHT, // Renamed from 's4Data'
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + (context.raw || '0');
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'center',
                            formatter: (value) => {
                                return value || '';
                            },
                            color: 'black'
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } else {
            console.error("Canvas element not found");
        }
    } else {
        console.log("No AHT data found for the specified account.");
    }
});

</script>
    <script>
        $(document).ready(function() {
            // Open popup and display the account's summary
            $("#popupButton").on("click", function() {
                const summaryContent = $(this).data("summary");
                $("#summaryContent").text(summaryContent);
                $("#popupModal").modal('show');
            });

            // Close popup actions
            $("#popupModal .close").on("click", function() {
                $("#popupModal").modal('hide');
            });
        });
    </script>

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


<script>
$(document).ready(function() {
  // Ensure the chatbot is closed by default on page load
  $('#chatbot').hide();

  // Extract the dynamic account name from the URL
  const urlParams = new URLSearchParams(window.location.search);
  const accountName = urlParams.get('name'); // Extract 'name' parameter from URL

  // Toggle Chatbot
  $('#chatbot-toggle').click(function () {
    $('#chatbot').slideToggle();
  });

  // Close Chatbot
  $('#chatbot-close').click(function () {
    $('#chatbot').slideUp();
  });

  // Send message function
  function sendMessage() {
    const userInput = $('#chatbot-input').val();
    if (userInput.trim() !== '') {
      // Append user's message to chat body with user-message class
      $('#chatbot-body').append('<p class="user-message"><strong>You:</strong> ' + userInput + '</p>');
      $('#chatbot-input').val(''); // Clear the input field

      // Show loading animation while waiting for chatbot reply
      $('#chatbot-body').append('<p class="loading">Chatbot is typing...</p>');
      $('#chatbot-body').scrollTop($('#chatbot-body')[0].scrollHeight); // Scroll to the bottom

      // Send user input and account name to chat.php
      $.ajax({
        url: 'chat.php', // Path to your PHP file
        type: 'POST',
        data: { 
          user_input: userInput, 
          account_name: accountName // Include account name dynamically
        },
		success: function (response) {
			$('.loading').remove(); 

			if (response.includes("<table")) {
				// If response contains a table, render it inside a div
				$('#chatbot-body').append('<div class="chatbot-message"><strong>Chatbot:</strong> <br>' + response + '</div>');
			} else {
				$('#chatbot-body').append('<p class="chatbot-message"><strong>Chatbot:</strong> ' + response + '</p>');
			}

			$('#chatbot-body').scrollTop($('#chatbot-body')[0].scrollHeight); // Scroll to the bottom
		},

      });
    }
  }

  // Send message when clicking "Send" button
  $('#chatbot-send').click(function () {
    sendMessage();
  });

  // Send message when pressing Enter key
  $('#chatbot-input').keypress(function (e) {
    if (e.which == 13) { // Enter key
      sendMessage();
    }
  });

  // Clear chat functionality
  $('#chatbot-clear').click(function() {
    $('#chatbot-body').html(''); // Clear the chat history
  });
});
</script>



<script>
document.addEventListener("DOMContentLoaded", function() {
    // Ensure ChartDataLabels plugin is registered
    Chart.register(ChartDataLabels);

    // Ensure caseData is only declared once
    const caseData = <?php echo json_encode($caseData); ?>;
    console.log("Case Data:", caseData);

    // Check if caseData is available
    if (caseData && caseData.length > 0) {
        // Static months from February 2024 to January 2025
        var labels = [
            "2024-April", "2024-May", "2024-June",
            "2024-July", "2024-August", "2024-September", "2024-October", "2024-November",
            "2024-December", "2025-January", "2025-February", "2025-March"
        ];

        var s1Data = Array(12).fill('');
        var s2Data = Array(12).fill('');
        var s3Data = Array(12).fill('');
        var s4Data = Array(12).fill('');
        var accountNames = Array(12).fill('');

        // Parse case data and populate arrays
        caseData.forEach(function(caseEntry) {
            var month = caseEntry.Month;
            var severity = caseEntry.Severity;
            var count = parseInt(caseEntry.Count);
            var accountName = caseEntry['Case Account Name'];

            var monthIndex = labels.indexOf(month);
            if (monthIndex > -1) {
                switch (severity) {
                    case "S1":
                        s1Data[monthIndex] = count > 0 ? count : '';
                        break;
                    case "S2":
                        s2Data[monthIndex] = count > 0 ? count : '';
                        break;
                    case "S3":
                        s3Data[monthIndex] = count > 0 ? count : '';
                        break;
                    case "S4":
                        s4Data[monthIndex] = count > 0 ? count : '';
                        break;
                }
                accountNames[monthIndex] = accountName;
            }
        });

        console.log("Processed Data:", { labels, s1Data, s2Data, s3Data, s4Data });

        var canvas = document.getElementById("barChart");
        if (canvas) {
            var barChart = canvas.getContext("2d");

            var myBarChart = new Chart(barChart, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: "S1 Cases",
                            backgroundColor: "rgb(245, 191, 186)",
                            borderColor: "rgb(255, 0, 0)",
                            borderWidth: 1,
                            data: s1Data
                        },
                        {
                            label: "S2 Cases",
                            backgroundColor: "rgb(249, 222, 178)",
                            borderColor: "rgb(255, 127, 62)",
                            borderWidth: 1,
                            data: s2Data
                        },
                        {
                            label: "S3 Cases",
                            backgroundColor: "rgb(247, 249, 178)",
                            borderColor: "rgb(255, 201, 74)",
                            borderWidth: 1,
                            data: s3Data
                        },
                        {
                            label: "S4 Cases",
                            backgroundColor: "rgb(178, 249, 182)",
                            borderColor: "rgb(6, 208, 1)",
                            borderWidth: 1,
                            data: s4Data
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + (context.raw || '0');
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'center',
                            formatter: (value) => {
                                return value || '';
                            },
                            color: 'black'
                        }
                    }
                }
            });
        } else {
            console.error("Canvas element not found");
        }
    } else {
        console.log("No case data found for the specified account.");
    }
});

</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const caseData1 = <?php echo json_encode($caseData1); ?>;
    console.log("Out-Flow Case Data:", caseData1);

    if (caseData1 && caseData1.length > 0) {
        var labelsOutFlow = [
            "2024-April", "2024-May", "2024-June",
            "2024-July", "2024-August", "2024-September", "2024-October", "2024-November",
            "2024-December", "2025-January", "2025-February", "2025-March"
        ];

        var s1DataOutFlow = Array(12).fill('');
        var s2DataOutFlow = Array(12).fill('');
        var s3DataOutFlow = Array(12).fill('');
        var s4DataOutFlow = Array(12).fill('');

        caseData1.forEach(function (caseEntry) {
            var month = caseEntry.Month;
            var severity = caseEntry.Severity;
            var count = parseInt(caseEntry.Count);

            var monthIndex = labelsOutFlow.indexOf(month);
            if (monthIndex > -1) {
                switch (severity) {
                    case "S1":
                        s1DataOutFlow[monthIndex] = count || '';
                        break;
                    case "S2":
                        s2DataOutFlow[monthIndex] = count || '';
                        break;
                    case "S3":
                        s3DataOutFlow[monthIndex] = count || '';
                        break;
                    case "S4":
                        s4DataOutFlow[monthIndex] = count || '';
                        break;
                }
            }
        });

        var canvasOutFlow = document.getElementById("barChart1");
        if (canvasOutFlow) {
            var barChartOutFlow = canvasOutFlow.getContext("2d");

            new Chart(barChartOutFlow, {
                type: "bar",
                data: {
                    labels: labelsOutFlow,
                    datasets: [
                        {
                            label: "S1 Cases",
                            backgroundColor: "rgb(245, 191, 186)",
                            borderColor: "rgb(255, 0, 0)",
                            borderWidth: 1,
                            data: s1DataOutFlow
                        },
                        {
                            label: "S2 Cases",
                            backgroundColor: "rgb(249, 222, 178)",
                            borderColor: "rgb(255, 127, 62)",
                            borderWidth: 1,
                            data: s2DataOutFlow
                        },
                        {
                            label: "S3 Cases",
                            backgroundColor: "rgb(247, 249, 178)",
                            borderColor: "rgb(255, 201, 74)",
                            borderWidth: 1,
                            data: s3DataOutFlow
                        },
                        {
                            label: "S4 Cases",
                            backgroundColor: "rgb(178, 249, 182)",
                            borderColor: "rgb(6, 208, 1)",
                            borderWidth: 1,
                            data: s4DataOutFlow
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + (context.raw || '0');
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'center',
                            formatter: function (value) {
                                return value ? value : '';
                            },
                            color: '#000',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } else {
            console.error("Canvas element for Out-Flow not found.");
        }
    } else {
        console.log("No Out-Flow case data found.");
    }
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof fixData !== 'undefined' && fixData.length > 0) {
            var totalHandling = 0;
            var totalResolvedWithoutFix = 0;
            var totalNewCodeFix = 0;
            var totalExistingCodeFix = 0;

            // Aggregate totals
            fixData.forEach(function(entry) {
                totalHandling += parseInt(entry.Handling) || 0;
                totalResolvedWithoutFix += parseInt(entry.Resolved_Without_FIx) || 0;
                totalNewCodeFix += parseInt(entry.New_Code_Fix_Provided) || 0;
                totalExistingCodeFix += parseInt(entry.Existing_Code_Fix_Provided) || 0;
            });

            // Prepare data for doughnut chart with conditional checks
            const dataForChart = [
                totalHandling > 0 ? totalHandling : '', 
                totalResolvedWithoutFix > 0 ? totalResolvedWithoutFix : '', 
                totalNewCodeFix > 0 ? totalNewCodeFix : '',
                totalExistingCodeFix > 0 ? totalExistingCodeFix : ''
            ];

            // Data for doughnut chart
            var doughnutChart = document.getElementById("doughnutChart").getContext("2d");
            var myDoughnutChart = new Chart(doughnutChart, {
                type: "doughnut",
                data: {
                    datasets: [{
                        data: dataForChart,
                        backgroundColor: ["#f9b2b2", "#f4927c", "#75faf2", "#f4e97c"], // Added color for new code fix
                        borderWidth: 0
                    }],
                    labels: ["Handling", "Resolved Without Fix", "New Code Fix Provided", "Existing Code Fix Provided"]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
						datalabels: {
							color: '#000000', // Change to black or another contrasting color
							display: true,
							anchor: 'end',
							align: 'start',
							font: {
								weight: 'bold',
								size: 10  // Reduced font size from 14 to 10
							},
							formatter: function(value) {
								return value || ''; // Show blank for 0
							},
							backgroundColor: 'rgba(0, 0, 0, 0)', // Keep the background transparent
							borderRadius: 0,
							padding: 6
						},
                        legend: {
                            position: "bottom",
                            labels: {
                                fontColor: "rgb(154, 154, 154)",
                                fontSize: 11,
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    tooltips: false,
                    layout: {
                        padding: {
                            left: 20,
                            right: 20,
                            top: 20,
                            bottom: 20
                        }
                    }
                },
                plugins: [ChartDataLabels]  // Enable the Datalabels plugin
            });
        } else {
            console.log("No fix data found for the specified account.");
        }
    });
</script>



	

  </body>
</html>