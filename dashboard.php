<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include 'db.php';
include 'auth.php';

// Initialize variable to hold fetched data
$caseData = [];

// Define the account name as "total"
$account_name = 'total';

// Determine the current year and the last 12 months
$currentDate = new DateTime();
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $pastDate = (clone $currentDate)->modify("-$i months");
    $months[] = $pastDate->format('Y-F'); // Format as "YYYY-Month"
}

// Prepare and execute the SQL query for the last 12 months
$stmt = $conn->prepare("
    SELECT Month, Severity, Count 
    FROM in_flow
    WHERE AccountName = ? AND Month IN (" . implode(',', array_fill(0, count($months), '?')) . ")
");

// Bind parameters
$stmt->bind_param(str_repeat('s', count($months) + 1), $account_name, ...$months);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all case data
while ($row = $result->fetch_assoc()) {
    $caseData[] = $row;
}

// Close the statement
$stmt->close();

// Convert the case data array to JSON
$caseDataJson = json_encode($caseData);

// Initialize variable to hold fetched data for Out-Flow
$outFlowData = [];

// Prepare and execute the SQL query for the last 12 months for Out-Flow
$stmt = $conn->prepare("
    SELECT Month, Severity, Count 
    FROM out_flow
    WHERE AccountName = ? AND Month IN (" . implode(',', array_fill(0, count($months), '?')) . ")
");

// Bind parameters for Out-Flow
$stmt->bind_param(str_repeat('s', count($months) + 1), $account_name, ...$months);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all Out-Flow case data
while ($row = $result->fetch_assoc()) {
    $outFlowData[] = $row;
}

// Close the statement
$stmt->close();

// JSON encode the data for use in JavaScript
$outFlowDataJson = json_encode($outFlowData);


// Initialize variable to hold fetched data
$fixData = [];

if (!empty($account_name)) {
    // Determine the last 12 months
    $lastTwelveMonths = [];
    for ($i = 0; $i < 12; $i++) {
        $month = date('F', strtotime("-$i month"));
        $lastTwelveMonths[] = $month;
    }
    $lastTwelveMonths = array_reverse($lastTwelveMonths); // From oldest to newest

    // Prepare and execute the SQL query for the last 12 months
    $placeholders = implode(',', array_fill(0, count($lastTwelveMonths), '?'));
    $stmt = $conn->prepare("
        SELECT Month, 
               SUM(New_Code_Fix_Provided) AS New_Code_Fix_Provided, 
               SUM(Existing_Code_Fix_Provided) AS Existing_Code_Fix_Provided, 
               SUM(Resolved_Without_FIx) AS Resolved_Without_FIx,
               SUM(Handling) AS Handling
        FROM Fix_No_Fix
        WHERE AccountName = ? AND Month IN ($placeholders)
        GROUP BY Month
    ");
    $stmt->bind_param(str_repeat('s', count($lastTwelveMonths) + 1), $account_name, ...$lastTwelveMonths);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all fix data and initialize an array with zero values for each month
    $fixData = array_fill_keys($lastTwelveMonths, [
        'New_Code_Fix_Provided' => 0,
        'Resolved_Without_FIx' => 0,
        'Resolved_With_FIx' => 0
    ]);

    while ($row = $result->fetch_assoc()) {
        $fixData[$row['Month']] = $row;
    }

    // Close the statement
    $stmt->close();
}

// Fetch the last run date
$query = "SELECT Last_Run FROM `lastrun` ORDER BY Last_Run DESC LIMIT 1"; // Fetch latest Last_Run
$result = mysqli_query($conn, $query);
$lastRunDate = null;

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $lastRunDate = $row['Last_Run'];
}


// Prepare the SQL query to fetch data from the milestonereport table
$sql = "SELECT EffectiveFrom, MilestoneName, AccountName, AccountRelease, ProductLine, MilestoneSubType, FromPB, ToPB, SupportAccountManager FROM milestonereport";
$result = $conn->query($sql);



$tableName = 'milestonereport'; // Replace with your actual table name
$lastUpdateQuery = "SHOW TABLE STATUS LIKE '$tableName'";
$lastUpdateResult = $conn->query($lastUpdateQuery);
$lastUpdateDate = '';

if ($lastUpdateResult && $lastUpdateResult->num_rows > 0) {
    $lastUpdateRow = $lastUpdateResult->fetch_assoc();
    $lastUpdateDate = $lastUpdateRow['Update_time'];
}

// Output the data to JavaScript
echo '<script>var fixData = ' . json_encode($fixData) . ';</script>';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
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
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />


    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />

   <style>
    .navbar {
      background-color: black !important; /* Override default background color */
    }
  </style>
  <style>
    .logout-btn {
      color: #000000; /* Light gray text color */
      background-color: #ffffff; /* Dark background color */
      border-radius: 4px; /* Optional: rounded corners */
      padding: 8px 12px; /* Padding for better spacing */
      text-decoration: none; /* Remove underline from the link */
    }

    .logout-btn:hover {
      background-color: #555; /* Slightly lighter gray on hover */
      color: #fff; /* White text color on hover */
    }
  </style>
  	<style>
	.chatbot-container {
	  position: fixed;
	  bottom: 0;
	  right: 20px;
	  width: 500px;
	  max-width: 90%;
	  height: 600px; /* Ensure fixed height */
	  display: flex; /* Flexbox to split the header, body, and footer */
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
	  flex: 1; /* Allow the body to fill the remaining height */
	  overflow-y: auto; /* Enable vertical scrolling */
	  padding: 10px;
	  background: #f9f9f9;
	}

	.chatbot-body::-webkit-scrollbar {
	  width: 8px; /* Width of the scrollbar */
	}

	.chatbot-body::-webkit-scrollbar-track {
	  background: #f1f1f1; /* Track color */
	  border-radius: 10px;
	}

	.chatbot-body::-webkit-scrollbar-thumb {
	  background: #007bff; /* Scrollbar thumb color */
	  border-radius: 10px;
	}

	.chatbot-body::-webkit-scrollbar-thumb:hover {
	  background: #0056b3; /* Darker shade when hovering */
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
	  background: #dc3545; /* Red for danger/clear */
	  color: #fff;
	  border: none;
	  border-radius: 5px;
	  cursor: pointer;
	}

	#chatbot-clear:hover {
	  background: #c82333; /* Darker red on hover */
	}
	/* Chatbot body styles */
	.chatbot-body {
	  flex: 1;
	  overflow-y: auto;
	  padding: 10px;
	  background: #f9f9f9;
	  color: #333; /* Default text color */
	  max-height: 500px; /* Limit height of chat container */
	}

	/* User message styling */
	.chatbot-body .user-message {
	  color: #007bff; /* Blue for user messages */
	  font-weight: bold;
	  text-align: right; /* Align user messages to the right */
	}

	/* Chatbot message styling */
	.chatbot-body .chatbot-message {
	  color: #28a745; /* Green for chatbot responses */
	  font-weight: bold;
	  text-align: left; /* Align chatbot messages to the left */
	}

	/* Loading animation */
	.chatbot-body .loading {
	  text-align: center;
	  color: #999;
	  font-style: italic;
	  padding: 5px;
	}
	</style>



	<style>
    .navbar {
      background-color: black !important; /* Override default background color */
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
  <a href="About/AboutL1.html" target="_blank">
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

          <!-- Navbar Header -->
<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
  <div class="container-fluid">
    <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
      <!-- Removed search functionality -->
    </nav>
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
          <div class="page-inner">
            <div
              class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4"
            >
              <div>
                <h3 class="fw-bold mb-3">Home Page</h3>
              </div>

            </div>
       

		<div class="row">
		    <div class="col-md-6 adjust-position">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Account List</h4>
            </div>
            <div class="card-body pb-0" style="height: 500px; overflow-y: auto;">
                <div class="table-responsive">
                    <table id="account-table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Account</th>
                            </tr>
                        </thead>
                        <tbody>

<tr><td><a href="index.php?name=A1%20Austria">A1 Austria</a></td></tr>
<tr><td><a href="index.php?name=A1%20Bulgaria">A1 Bulgaria</a></td></tr>
<tr><td><a href="index.php?name=A1%20Croatia">A1 Croatia</a></td></tr>
<tr><td><a href="index.php?name=A1%20Macedonia">A1 Macedonia</a></td></tr>
<tr><td><a href="index.php?name=A1%20Telekom%20Austria%20Group">A1 Telekom Austria Group</a></td></tr>
<tr><td><a href="index.php?name=AAPT">AAPT</a></td></tr>
<tr><td><a href="index.php?name=ABN%20AMRO%20Bank%20N.V.">ABN AMRO Bank N.V.</a></td></tr>
<tr><td><a href="index.php?name=Accenture">Accenture</a></td></tr>
<tr><td><a href="index.php?name=Advanced%20Info%20Service%20plc.">Advanced Info Service plc.</a></td></tr>
<tr><td><a href="index.php?name=Algar%20Telecom">Algar Telecom</a></td></tr>
<tr><td><a href="index.php?name=Altice%20USA">Altice USA</a></td></tr>
<tr><td><a href="index.php?name=Amdocs%20GSS">Amdocs GSS</a></td></tr>
<tr><td><a href="index.php?name=Amdocs%20Ltd.">Amdocs Ltd.</a></td></tr>
<tr><td><a href="index.php?name=Amdocs%20Product%20Group">Amdocs Product Group</a></td></tr>
<tr><td><a href="index.php?name=America%20Movil%20S.A.%20de%20C.V.">America Movil S.A. de C.V.</a></td></tr>
<tr><td><a href="index.php?name=Antel">Antel</a></td></tr>
<tr><td><a href="index.php?name=Arelion%20Sweden%20AB">Arelion Sweden AB</a></td></tr>
<tr><td><a href="index.php?name=ArmenTel">ArmenTel</a></td></tr>
<tr><td><a href="index.php?name=<?php echo urlencode('AT&T Business Solutions'); ?>">AT&T Business Solutions</a></td></tr>
<tr><td><a href="index.php?name=<?php echo urlencode('AT&T Consumer'); ?>">AT&T Consumer</a></td></tr>
<tr><td><a href="index.php?name=<?php echo urlencode('AT&T Cricket'); ?>">AT&T Cricket</a></td></tr>
<tr><td><a href="index.php?name=<?php echo urlencode('AT&T Inc.'); ?>">AT&T Inc.</a></td></tr>
<tr><td><a href="index.php?name=<?php echo urlencode('AT&T LATAM'); ?>">AT&T LATAM</a></td></tr>
<tr><td><a href="index.php?name=<?php echo urlencode('AT&T Openet'); ?>">AT&T Openet</a></td></tr>
<tr><td><a href="index.php?name=Axtel">Axtel</a></td></tr>
<tr><td><a href="index.php?name=Azercell">Azercell</a></td></tr>
<tr><td><a href="index.php?name=Bank%20Hapoalim%20B.M.">Bank Hapoalim B.M.</a></td></tr>
<tr><td><a href="index.php?name=Bell%20Canada">Bell Canada</a></td></tr>
<tr><td><a href="index.php?name=Bell%20Mobility%20Inc.">Bell Mobility Inc.</a></td></tr>
<tr><td><a href="index.php?name=Beyond%20Cable%20Inc">Beyond Cable Inc</a></td></tr>
<tr><td><a href="index.php?name=BEZEQ%20The%20Israel%20Telecommunication%20Corp.%20Ltd.">BEZEQ The Israel Telecommunication Corp. Ltd.</a></td></tr>
<tr><td><a href="index.php?name=Bharat%20Sanchar%20Nigam%20Ltd">Bharat Sanchar Nigam Ltd</a></td></tr>
<tr><td><a href="index.php?name=Bharti%20Airtel">Bharti Airtel</a></td></tr>
<tr><td><a href="index.php?name=Biglobe%20Inc.">Biglobe Inc.</a></td></tr>
<tr><td><a href="index.php?name=Botswana%20Telecommunications%20Corporation">Botswana Telecommunications Corporation</a></td></tr>
<tr><td><a href="index.php?name=BT%20Germany">BT Germany</a></td></tr>
<tr><td><a href="index.php?name=BT%20Global%20Services">BT Global Services</a></td></tr>
<tr><td><a href="index.php?name=BT%20Group%20Plc">BT Group Plc</a></td></tr>
<tr><td><a href="index.php?name=BT%20Ignite%20Espana">BT Ignite Espana</a></td></tr>
<tr><td><a href="index.php?name=BT%20Ireland">BT Ireland</a></td></tr>
<tr><td><a href="index.php?name=BT%20Italia%20S.p.A.">BT Italia S.p.A.</a></td></tr>
<tr><td><a href="index.php?name=BT%20plc">BT plc</a></td></tr>
<tr><td><a href="index.php?name=C%20Spire">C Spire</a></td></tr>
<tr><td><a href="index.php?name=Cable%20&%20Wireless%20Communications%20Plc">Cable & Wireless Communications Plc</a></td></tr>
<tr><td><a href="index.php?name=Capita%20Business%20Services%20Limited">Capita Business Services Limited</a></td></tr>
<tr><td><a href="index.php?name=Cell%20C%20(Pty)%20Ltd">Cell C (Pty) Ltd</a></td></tr>
<tr><td><a href="index.php?name=Cellcom%20Israel%20Ltd.">Cellcom Israel Ltd.</a></td></tr>
<tr><td><a href="index.php?name=Charter%20Communications%20Inc">Charter Communications Inc</a></td></tr>
<tr><td><a href="index.php?name=Cincinnati%20Bell%20Inc.">Cincinnati Bell Inc.</a></td></tr>
<tr><td><a href="index.php?name=Claro%20Brasil">Claro Brasil</a></td></tr>
<tr><td><a href="index.php?name=Claro%20Chile">Claro Chile</a></td></tr>
<tr><td><a href="index.php?name=Claro%20Dominicana">Claro Dominicana</a></td></tr>
<tr><td><a href="index.php?name=Claro%20Ecuador">Claro Ecuador</a></td></tr>
<tr><td><a href="index.php?name=Claro%20Peru">Claro Peru</a></td></tr>
<tr><td><a href="index.php?name=Claro%20Puerto%20Rico">Claro Puerto Rico</a></td></tr>
<tr><td><a href="index.php?name=COLT%20Telecom%20Group%20plc">COLT Telecom Group plc</a></td></tr>
<tr><td><a href="index.php?name=Comcast%20Corporation">Comcast Corporation</a></td></tr>
<tr><td><a href="index.php?name=Comcel">Comcel</a></td></tr>
<tr><td><a href="index.php?name=Companhia%20de%20Telecomunicacoes%20de%20Macau%20S.A.R.L.">Companhia de Telecomunicacoes de Macau S.A.R.L.</a></td></tr>
<tr><td><a href="index.php?name=Comverse">Comverse</a></td></tr>
<tr><td><a href="index.php?name=Cosmote">Cosmote</a></td></tr>
<tr><td><a href="index.php?name=Crnogorski%20Telekom%20AD">Crnogorski Telekom AD</a></td></tr>
<tr><td><a href="index.php?name=Cyta">Cyta</a></td></tr>
<tr><td><a href="index.php?name=Delta%20Lloyd%20Life%20Belgium">Delta Lloyd Life Belgium</a></td></tr>
<tr><td><a href="index.php?name=Digicel%20(Jamaica)">Digicel (Jamaica)</a></td></tr>
<tr><td><a href="index.php?name=Digital%20Total%20Access%20Communications">Digital Total Access Communications</a></td></tr>
<tr><td><a href="index.php?name=Dish%20Network">Dish Network</a></td></tr>
<tr><td><a href="index.php?name=Eastlink">Eastlink</a></td></tr>
<tr><td><a href="index.php?name=eBay,%20Inc.">eBay, Inc.</a></td></tr>
<tr><td><a href="index.php?name=El%20Dorado%20Acquisition">El Dorado Acquisition</a></td></tr>
<tr><td><a href="index.php?name=Elisa">Elisa</a></td></tr>
<tr><td><a href="index.php?name=Embratel">Embratel</a></td></tr>
<tr><td><a href="index.php?name=Enel%20S.p.A.">Enel S.p.A.</a></td></tr>
<tr><td><a href="index.php?name=Euskaltel">Euskaltel</a></td></tr>
<tr><td><a href="index.php?name=Everything%20Everywhere%20Ltd.">Everything Everywhere Ltd.</a></td></tr>
<tr><td><a href="index.php?name=FairPoint%20Communications,%20Inc.">FairPoint Communications, Inc.</a></td></tr>
<tr><td><a href="index.php?name=Far%20EasTone">Far EasTone</a></td></tr>
<tr><td><a href="index.php?name=Fastweb%20SpA">Fastweb SpA</a></td></tr>
<tr><td><a href="index.php?name=Foxtel%20Management%20Pty%20Limited">Foxtel Management Pty Limited</a></td></tr>
<tr><td><a href="index.php?name=Free%20Senegal">Free Senegal</a></td></tr>
<tr><td><a href="index.php?name=Get">Get</a></td></tr>
<tr><td><a href="index.php?name=Global%20Village%20Telecom%20(GVT)">Global Village Telecom (GVT)</a></td></tr>
<tr><td><a href="index.php?name=Globe%20Telecom">Globe Telecom</a></td></tr>
<tr><td><a href="index.php?name=GO%20plc">GO plc</a></td></tr>
<tr><td><a href="index.php?name=Golan%20Telecom%20Ltd">Golan Telecom Ltd</a></td></tr>
<tr><td><a href="index.php?name=GrameenPhone">GrameenPhone</a></td></tr>
<tr><td><a href="index.php?name=Guyana%20Telephone%20and%20Telegraph%20Company%20Limited">Guyana Telephone and Telegraph Company Limited</a></td></tr>
<tr><td><a href="index.php?name=H3G%20Austria">H3G Austria</a></td></tr>
<tr><td><a href="index.php?name=Hawaiian%20Telcom">Hawaiian Telcom</a></td></tr>
<tr><td><a href="index.php?name=HOT%20Mobile">HOT Mobile</a></td></tr>
<tr><td><a href="index.php?name=HSBC%20Bank%20plc">HSBC Bank plc</a></td></tr>
<tr><td><a href="index.php?name=Hutchison%203G%20Ireland">Hutchison 3G Ireland</a></td></tr>
<tr><td><a href="index.php?name=Hutchison%203G%20UK">Hutchison 3G UK</a></td></tr>
<tr><td><a href="index.php?name=Hutchison%20CAT%20Wireless%20Multimedia%20/%20CAT%20CDMA">Hutchison CAT Wireless Multimedia / CAT CDMA</a></td></tr>
<tr><td><a href="index.php?name=Hutchison%20Global%20Enabling%20Services%20Limited">Hutchison Global Enabling Services Limited</a></td></tr>
<tr><td><a href="index.php?name=ICE">ICE</a></td></tr>
<tr><td><a href="index.php?name=Intelig">Intelig</a></td></tr>
<tr><td><a href="index.php?name=Interoute%20Telecommunications%20Ltd">Interoute Telecommunications Ltd</a></td></tr>
<tr><td><a href="index.php?name=INWI">INWI</a></td></tr>
<tr><td><a href="index.php?name=J:COM%20(Jupiter%20Telecom)">J:COM (Jupiter Telecom)</a></td></tr>
<tr><td><a href="index.php?name=Jazz%20Telecom%20(Jazztel)">Jazz Telecom (Jazztel)</a></td></tr>
<tr><td><a href="index.php?name=Jersey%20Telecom">Jersey Telecom</a></td></tr>
<tr><td><a href="index.php?name=Kabel%20Baden-Wurttemberg%20GmbH%20&%20Co.%20KG">Kabel Baden-Wurttemberg GmbH & Co. KG</a></td></tr>
<tr><td><a href="index.php?name=Kapsch%20AG">Kapsch AG</a></td></tr>
<tr><td><a href="index.php?name=KaR-Tel">KaR-Tel</a></td></tr>
<tr><td><a href="index.php?name=Kcell">Kcell</a></td></tr>
<tr><td><a href="index.php?name=KT%20Corp">KT Corp</a></td></tr>
<tr><td><a href="index.php?name=Kyivstar">Kyivstar</a></td></tr>
<tr><td><a href="index.php?name=LG%20Uplus%20Corp.">LG Uplus Corp.</a></td></tr>
<tr><td><a href="index.php?name=Liberty%20Global%20Europe%20N.V.">Liberty Global Europe N.V.</a></td></tr>
<tr><td><a href="index.php?name=LIME">LIME</a></td></tr>
<tr><td><a href="index.php?name=Lumen">Lumen</a></td></tr>
<tr><td><a href="index.php?name=Magenta%20Telekom">Magenta Telekom</a></td></tr>
<tr><td><a href="index.php?name=Magyar%20Telekom%20Plc.">Magyar Telekom Plc.</a></td></tr>
<tr><td><a href="index.php?name=Mascom%20Wireless%20Botswana%20Ltd.">Mascom Wireless Botswana Ltd.</a></td></tr>
<tr><td><a href="index.php?name=Maxis%20Communications">Maxis Communications</a></td></tr>
<tr><td><a href="index.php?name=Melita%20Ltd.">Melita Ltd.</a></td></tr>
<tr><td><a href="index.php?name=Melon%20Mobile">Melon Mobile</a></td></tr>
<tr><td><a href="index.php?name=MetroPCS%20Communications,%20Inc.">MetroPCS Communications, Inc.</a></td></tr>
<tr><td><a href="index.php?name=Mobile%20Telephone%20Networks%20(Pty)%20Ltd">Mobile Telephone Networks (Pty) Ltd</a></td></tr>
<tr><td><a href="index.php?name=MobileOne%20Asia">MobileOne Asia</a></td></tr>
<tr><td><a href="index.php?name=Network%20Rail">Network Rail</a></td></tr>
<tr><td><a href="index.php?name=NOS%20COMUNICA%C3%87%C3%95ES.%20S.A.">NOS COMUNICAÇÕES. S.A.</a></td></tr>
<tr><td><a href="index.php?name=NTT%20America">NTT America</a></td></tr>
<tr><td><a href="index.php?name=NTT%20DOCOMO,%20INC.">NTT DOCOMO, INC.</a></td></tr>
<tr><td><a href="index.php?name=NYCT">NYCT</a></td></tr>
<tr><td><a href="index.php?name=O2%20Czech%20Republic%20a.s.">O2 Czech Republic a.s.</a></td></tr>
<tr><td><a href="index.php?name=O2%20Slovakia,%20s.r.o.">O2 Slovakia, s.r.o.</a></td></tr>
<tr><td><a href="index.php?name=Open%20Fiber">Open Fiber</a></td></tr>
<tr><td><a href="index.php?name=Optus">Optus</a></td></tr>
<tr><td><a href="index.php?name=Orange%20Belgium">Orange Belgium</a></td></tr>
<tr><td><a href="index.php?name=Orange%20Egypt">Orange Egypt</a></td></tr>
<tr><td><a href="index.php?name=Orange%20France">Orange France</a></td></tr>
<tr><td><a href="index.php?name=Orange%20Liberia">Orange Liberia</a></td></tr>
<tr><td><a href="index.php?name=Orange%20Polska">Orange Polska</a></td></tr>
<tr><td><a href="index.php?name=Orange%20Slovensko">Orange Slovensko</a></td></tr>
<tr><td><a href="index.php?name=Orange%20Spain">Orange Spain</a></td></tr>
<tr><td><a href="index.php?name=PayPal">PayPal</a></td></tr>
<tr><td><a href="index.php?name=Pelephone%20Communications%20Ltd.">Pelephone Communications Ltd.</a></td></tr>
<tr><td><a href="index.php?name=Philippine%20Long%20Distance%20Telephone%20Company%20(PLDT)">Philippine Long Distance Telephone Company (PLDT)</a></td></tr>
<tr><td><a href="index.php?name=Play">Play</a></td></tr>
<tr><td><a href="index.php?name=Primacom">Primacom</a></td></tr>
<tr><td><a href="index.php?name=Rami-Levy">Rami-Levy</a></td></tr>
<tr><td><a href="index.php?name=redONE%20Network%20Sdn%20Bhd">redONE Network Sdn Bhd</a></td></tr>
<tr><td><a href="index.php?name=Rogers%20Wireless%20Inc">Rogers Wireless Inc</a></td></tr>
<tr><td><a href="index.php?name=SaskTel">SaskTel</a></td></tr>
<tr><td><a href="index.php?name=SES%20Networks">SES Networks</a></td></tr>
<tr><td><a href="index.php?name=SETAR%20N.V.">SETAR N.V.</a></td></tr>
<tr><td><a href="index.php?name=SFR%20(Societe%20Francaise%20de%20Radiotelephone)">SFR (Societe Francaise de Radiotelephone)</a></td></tr>
<tr><td><a href="index.php?name=SingTel">SingTel</a></td></tr>
<tr><td><a href="index.php?name=Sita%20Sc">Sita Sc</a></td></tr>
<tr><td><a href="index.php?name=Sky%20Italia%20SpA">Sky Italia SpA</a></td></tr>
<tr><td><a href="index.php?name=Sky%20UK%20Limited">Sky UK Limited</a></td></tr>
<tr><td><a href="index.php?name=Smart%20Communications">Smart Communications</a></td></tr>
<tr><td><a href="index.php?name=Smart.Net">Smart.Net</a></td></tr>
<tr><td><a href="index.php?name=Softbank%20Mobile%20Corp.">Softbank Mobile Corp.</a></td></tr>
<tr><td><a href="index.php?name=Sonaecom%20(BeArtis)">Sonaecom (BeArtis)</a></td></tr>
<tr><td><a href="index.php?name=Sprint">Sprint</a></td></tr>
<tr><td><a href="index.php?name=Sri%20Lanka%20Telecom">Sri Lanka Telecom</a></td></tr>
<tr><td><a href="index.php?name=Sunrise%20Telecom">Sunrise Telecom</a></td></tr>
<tr><td><a href="index.php?name=Tata%20Communications%20Ltd.%20(India)">Tata Communications Ltd. (India)</a></td></tr>
<tr><td><a href="index.php?name=Tata%20Consultancy%20Services">Tata Consultancy Services</a></td></tr>
<tr><td><a href="index.php?name=TelCell%20N.V.">TelCell N.V.</a></td></tr>
<tr><td><a href="index.php?name=TELE%20Greenland%20A/S">TELE Greenland A/S</a></td></tr>
<tr><td><a href="index.php?name=Tele2%20Sweden">Tele2 Sweden</a></td></tr>
<tr><td><a href="index.php?name=Telefonica%20Brazil">Telefonica Brazil</a></td></tr>
<tr><td><a href="index.php?name=Telefonica%20de%20Argentina">Telefonica de Argentina</a></td></tr>
<tr><td><a href="index.php?name=Telefonica%20Moviles%20Chile%20(Movistar)">Telefonica Moviles Chile (Movistar)</a></td></tr>
<tr><td><a href="index.php?name=Telefonica%20Moviles%20Peru%20(Movistar)">Telefonica Moviles Peru (Movistar)</a></td></tr>
<tr><td><a href="index.php?name=Telefonica%20O2%20Germany">Telefonica O2 Germany</a></td></tr>
<tr><td><a href="index.php?name=Telekom%20Deutschland%20Gmbh">Telekom Deutschland Gmbh</a></td></tr>
<tr><td><a href="index.php?name=Telekom%20Romania">Telekom Romania</a></td></tr>
<tr><td><a href="index.php?name=Telenet">Telenet</a></td></tr>
<tr><td><a href="index.php?name=Telenor%20Hungary">Telenor Hungary</a></td></tr>
<tr><td><a href="index.php?name=Telenor%20Serbia">Telenor Serbia</a></td></tr>
<tr><td><a href="index.php?name=TelePacific%20Communications">TelePacific Communications</a></td></tr>
<tr><td><a href="index.php?name=Telia%20Company">Telia Company</a></td></tr>
<tr><td><a href="index.php?name=Telia%20Eesti%20AS">Telia Eesti AS</a></td></tr>
<tr><td><a href="index.php?name=Telia%20Norway">Telia Norway</a></td></tr>
<tr><td><a href="index.php?name=Telkom%20SA%20Ltd.">Telkom SA Ltd.</a></td></tr>
<tr><td><a href="index.php?name=Telkomsel">Telkomsel</a></td></tr>
<tr><td><a href="index.php?name=Telmex%20Mexico">Telmex Mexico</a></td></tr>
<tr><td><a href="index.php?name=Telstra">Telstra</a></td></tr>
<tr><td><a href="index.php?name=TELUS%20Communications%20Company">TELUS Communications Company</a></td></tr>
<tr><td><a href="index.php?name=TELUS%20Mobility%20Inc.">TELUS Mobility Inc.</a></td></tr>
<tr><td><a href="index.php?name=Teracom%20AB">Teracom AB</a></td></tr>
<tr><td><a href="index.php?name=The%20Standard%20Bank%20of%20South%20Africa%20Limited">The Standard Bank of South Africa Limited</a></td></tr>
<tr><td><a href="index.php?name=TIM%20Brasil%20S.%20A.">TIM Brasil S. A.</a></td></tr>
<tr><td><a href="index.php?name=T-Mobile%20Czech%20Republic">T-Mobile Czech Republic</a></td></tr>
<tr><td><a href="index.php?name=T-Mobile%20Netherlands">T-Mobile Netherlands</a></td></tr>
<tr><td><a href="index.php?name=T-Mobile%20USA">T-Mobile USA</a></td></tr>
<tr><td><a href="index.php?name=TPG%20Telecom%20Limited">TPG Telecom Limited</a></td></tr>
<tr><td><a href="index.php?name=TracFone%20Wireless%20Inc.">TracFone Wireless Inc.</a></td></tr>
<tr><td><a href="index.php?name=Transpower%20New%20Zealand%20Ltd">Transpower New Zealand Ltd</a></td></tr>
<tr><td><a href="index.php?name=True%20Move">True Move</a></td></tr>
<tr><td><a href="index.php?name=Turk%20Telekom">Turk Telekom</a></td></tr>
<tr><td><a href="index.php?name=Turkcell">Turkcell</a></td></tr>
<tr><td><a href="index.php?name=Unitel%20(Beeline)">Unitel (Beeline)</a></td></tr>
<tr><td><a href="index.php?name=UPC">UPC</a></td></tr>
<tr><td><a href="index.php?name=UPC%20Holding%20BV">UPC Holding BV</a></td></tr>
<tr><td><a href="index.php?name=US%20Cellular%20Corporation">US Cellular Corporation</a></td></tr>
<tr><td><a href="index.php?name=UTS">UTS</a></td></tr>
<tr><td><a href="index.php?name=UzaCI%20RTMS">UzaCI RTMS</a></td></tr>
<tr><td><a href="index.php?name=Vadsa">Vadsa</a></td></tr>
<tr><td><a href="index.php?name=Veon">Veon</a></td></tr>
<tr><td><a href="index.php?name=VeriFone%20Inc">VeriFone Inc</a></td></tr>
<tr><td><a href="index.php?name=Verizon%20Wireless%20Inc.">Verizon Wireless Inc.</a></td></tr>
<tr><td><a href="index.php?name=Viasat,%20Inc.">Viasat, Inc.</a></td></tr>
<tr><td><a href="index.php?name=Viettel">Viettel</a></td></tr>
<tr><td><a href="index.php?name=Vodacom%20Congo%20(DRC)%20s.p.r.l.">Vodacom Congo (DRC) s.p.r.l.</a></td></tr>
<tr><td><a href="index.php?name=Vodacom%20Mozambique">Vodacom Mozambique</a></td></tr>
<tr><td><a href="index.php?name=Vodacom%20Tanzania%20Limited">Vodacom Tanzania Limited</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Albania">Vodafone Albania</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20CR">Vodafone CR</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20D2%20GmbH">Vodafone D2 GmbH</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Ghana">Vodafone Ghana</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Group%20plc">Vodafone Group plc</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Hungary">Vodafone Hungary</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Hutchison%20Australia">Vodafone Hutchison Australia</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20India%20Limited">Vodafone India Limited</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Ireland">Vodafone Ireland</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Italy">Vodafone Italy</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Netherlands">Vodafone Netherlands</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20New%20Zealand">Vodafone New Zealand</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Oman">Vodafone Oman</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Portugal">Vodafone Portugal</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Romania">Vodafone Romania</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Spain">Vodafone Spain</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Turkey%20(Telsim)">Vodafone Turkey (Telsim)</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20UK%20(Fixed)">Vodafone UK (Fixed)</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20UK%20(Mobile)">Vodafone UK (Mobile)</a></td></tr>
<tr><td><a href="index.php?name=Vodafone%20Ukraine">Vodafone Ukraine</a></td></tr>
<tr><td><a href="index.php?name=Wind">Wind</a></td></tr>
<tr><td><a href="index.php?name=XL%20Axiata">XL Axiata</a></td></tr>
<tr><td><a href="index.php?name=Yettel%20Bulgaria">Yettel Bulgaria</a></td></tr>
<tr><td><a href="index.php?name=Ziggo">Ziggo</a></td></tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
	
		  <div class="col-md-6 adjust-position">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">SAM List</h4>
      </div>
      <div class="card-body pb-0" style="height: 500px; overflow-y: auto;">
        <div class="card-body">
          <div class="table-responsive">
            <table id="multi-filter-select" class="display table table-striped table-hover">
              <thead>
                <tr>
                  <th>SAM Name</th>
                  <th>Account Name</th>
                </tr>
              </thead>

    <tbody>
<tr><td>Adir Atias</td><td>Cellcom Israel Ltd.</td></tr>
<tr><td>Adir Atias</td><td>KCOM</td></tr>
<tr><td>Adir Atias</td><td>Liquid Telecom</td></tr>
<tr><td>Adir Atias</td><td>Telenet</td></tr>
<tr><td>Adir Atias</td><td>Vodafone Albania</td></tr>
<tr><td>Adir Atias</td><td>Vodafone CR</td></tr>
<tr><td>Adir Atias</td><td>Vodafone D2 GmbH</td></tr>
<tr><td>Adir Atias</td><td>Vodafone Greece</td></tr>
<tr><td>Adir Atias</td><td>Vodafone Hungary</td></tr>
<tr><td>Adir Atias</td><td>Vodafone Portugal</td></tr>
<tr><td>Adir Atias</td><td>Vodafone Romania</td></tr>
<tr><td>Adir Atias</td><td>Vodafone Solstice</td></tr>
<tr><td>Adir Atias</td><td>Vodafone Spain</td></tr>
<tr><td>Alfredo Gallegos</td><td>Claro DR</td></tr>
<tr><td>Alfredo Gallegos</td><td>Claro Puerto Rico</td></tr>
<tr><td>Alfredo Gallegos</td><td>Comcel/AMX Chile</td></tr>
<tr><td>Animesh Kishore Prasad</td><td>Claro Brasil</td></tr>
<tr><td>Animesh Kishore Prasad</td><td>Mobile TeleSystems (MTS)</td></tr>
<tr><td>Animesh Kishore Prasad</td><td>PPF</td></tr>
<tr><td>Animesh Kishore Prasad</td><td>Telkom SA Ltd.</td></tr>
<tr><td>Arvinder Kaur</td><td>Maxis</td></tr>
<tr><td>Arvinder Kaur</td><td>TATA Communications</td></tr>
<tr><td>Arvinder Kaur</td><td>Vodafone Group plc</td></tr>
<tr><td>Arvinder Kaur</td><td>Vodafone UK (Fixed)</td></tr>
<tr><td>Gyan Prakash</td><td>A1 Austria</td></tr>
<tr><td>Gyan Prakash</td><td>A1 Bulgaria</td></tr>
<tr><td>Gyan Prakash</td><td>A1 Croatia</td></tr>
<tr><td>Gyan Prakash</td><td>A1 Macedonia Postpaid</td></tr>
<tr><td>Gyan Prakash</td><td>A1 Slovenia</td></tr>
<tr><td>Gyan Prakash</td><td>America Movil S.A. de C.V.</td></tr>
<tr><td>Gyan Prakash</td><td>Get</td></tr>
<tr><td>Gyan Prakash</td><td>Telefonica de Argentina</td></tr>
<tr><td>Gyan Prakash</td><td>Telefonica del Peru</td></tr>
<tr><td>Gyan Prakash</td><td>Telefonica Moviles Chile (Movistar)</td></tr>
<tr><td>Inbal Sharifi</td><td>BT Ignite Espana</td></tr>
<tr><td>Inbal Sharifi</td><td>BT plc</td></tr>
<tr><td>Inbal Sharifi</td><td>Everything Everywhere Ltd.</td></tr>
<tr><td>Inbal Sharifi</td><td>NTL Group Ltd</td></tr>
<tr><td>Inbal Sharifi</td><td>Primacom</td></tr>
<tr><td>Inbal Sharifi</td><td>SES Networks</td></tr>
<tr><td>Inbal Sharifi</td><td>Sunrise Telecom</td></tr>
<tr><td>Inbal Sharifi</td><td>UPC</td></tr>
<tr><td>Inbal Sharifi</td><td>UPC Holding BV</td></tr>
<tr><td>Inbal Sharifi</td><td>Virgin Media Holdings Inc.</td></tr>
<tr><td>Kiran Koduri</td><td>TRUE</td></tr>
<tr><td>Limor Yanai</td><td>Axtel</td></tr>
<tr><td>Limor Yanai</td><td>Enel S.p.A.</td></tr>
<tr><td>Limor Yanai</td><td>Fastweb SpA</td></tr>
<tr><td>Limor Yanai</td><td>Interoute Telecommunications Ltd/Exa Infrastructure UK Limited</td></tr>
<tr><td>Limor Yanai</td><td>Lumen (Centurylink)</td></tr>
<tr><td>Limor Yanai</td><td>NET</td></tr>
<tr><td>Limor Yanai</td><td>Oi (Telemar)</td></tr>
<tr><td>Limor Yanai</td><td>Safaricom Ltd.</td></tr>
<tr><td>Limor Yanai</td><td>Telefonica Brazil</td></tr>
<tr><td>Limor Yanai</td><td>Bank Hapoalim B.M</td></tr>
<tr><td>Limor Yanai</td><td>TIM Brasil S. A.</td></tr>
<tr><td>Mahendra Gondaliya</td><td>XL Axiata</td></tr>
<tr><td>Michal Massa</td><td>Bell Canada</td></tr>
<tr><td>Michal Massa</td><td>Rogers</td></tr>
<tr><td>Miri Cohen</td><td>ATT BSSe</td></tr>
<tr><td>Moshe Atar</td><td>Cosmote</td></tr>
<tr><td>Moshe Atar</td><td>Crnogorski Telekom AD</td></tr>
<tr><td>Moshe Atar</td><td>Elion Enterprises (Eesti Telefon)</td></tr>
<tr><td>Moshe Atar</td><td>Hutchison 3G Ireland</td></tr>
<tr><td>Moshe Atar</td><td>JSC Kazakhtelecom</td></tr>
<tr><td>Moshe Atar</td><td>Kcell</td></tr>
<tr><td>Moshe Atar</td><td>Kyivstar</td></tr>
<tr><td>Moshe Atar</td><td>Magyar Telekom Plc.</td></tr>
<tr><td>Moshe Atar</td><td>SFR (Societe Francaise de Radiotelephone)</td></tr>
<tr><td>Moshe Atar</td><td>Sky Italia SpA</td></tr>
<tr><td>Moshe Atar</td><td>Sky UK Limited</td></tr>
<tr><td>Moshe Atar</td><td>Slovak Telekom (T-Com)</td></tr>
<tr><td>Moshe Atar</td><td>Svyazinvest</td></tr>
<tr><td>Moshe Atar</td><td>Telekom Romania</td></tr>
<tr><td>Moshe Atar</td><td>Telia Company</td></tr>
<tr><td>Moshe Atar</td><td>Telia Norway</td></tr>
<tr><td>Moshe Atar</td><td>Veon</td></tr>
<tr><td>Moshe Atar</td><td>Vodafone Ireland</td></tr>
<tr><td>Moshe Atar</td><td>Vodafone Italy</td></tr>
<tr><td>Moshe Atar</td><td>Vodafone Netherlands</td></tr>
<tr><td>Moshe Atar</td><td>Hutchison 3G UK</td></tr>
<tr><td>Moshe Atar</td><td>VeriFone Inc</td></tr>
<tr><td>Moshe Atar</td><td>Ziggo</td></tr>
<tr><td>Nitin Gupta</td><td>Globe</td></tr>
<tr><td>Ornit Sapir</td><td>Far EasTone</td></tr>
<tr><td>Ornit Sapir</td><td>Korea Telecom</td></tr>
<tr><td>Ornit Sapir</td><td>LGU</td></tr>
<tr><td>Pankaj Jain</td><td>US Cellular Corporation</td></tr>
<tr><td>Pankaj Jain</td><td>AT&T LATAM</td></tr>
<tr><td>Pankaj Jain</td><td>ATT Mobility</td></tr>
<tr><td>Pankaj Jain</td><td>Network Rail</td></tr>
<tr><td>Pankaj Jain</td><td>Pelephone Communications Ltd.</td></tr>
<tr><td>Pankaj Jain</td><td>Telenor Denmark</td></tr>
<tr><td>Pankaj Jain</td><td>Telenor Hungary</td></tr>
<tr><td>Pankaj Jain</td><td>Telenor Serbia</td></tr>
<tr><td>Pankaj Jain</td><td>Telenor Romania</td></tr>
<tr><td>Ranjoo Singh</td><td>AT&T Cricket</td></tr>
<tr><td>Ranjoo Singh</td><td>ATT UVerse & Cricket (C1)</td></tr>
<tr><td>Ranjoo Singh</td><td>Colt</td></tr>
<tr><td>Ranjoo Singh</td><td>Elisa</td></tr>
<tr><td>Ranjoo Singh</td><td>ICE</td></tr>
<tr><td>Ranjoo Singh</td><td>Orange Romania</td></tr>
<tr><td>Ranjoo Singh</td><td>Sonaecom (BeArtis)</td></tr>
<tr><td>Ranjoo Singh</td><td>Teracom AB</td></tr>
<tr><td>Ranjoo Singh</td><td>TELUS Communications Company</td></tr>
<tr><td>Ranjoo Singh</td><td>T-Mobile Czech Republic</td></tr>
<tr><td>Smadar</td><td>Verizon Wireless Inc.</td></tr>
<tr><td>Swapnil Doifode</td><td>Astro</td></tr>
<tr><td>Swapnil Doifode</td><td>Optus</td></tr>
<tr><td>Swapnil Doifode</td><td>Singtel (BCC)</td></tr>
<tr><td>Swapnil Doifode</td><td>Vodafone India Limited</td></tr>
<tr><td>Yuval Shemesh</td><td>Azercell</td></tr>
<tr><td>Yuval Shemesh</td><td>Telstra</td></tr>
<tr><td>Yuval Shemesh</td><td>Transpower</td></tr>
<tr><td>Zion Gabay</td><td>Altice USA</td></tr>
<tr><td>Zion Gabay</td><td>Comcast Mid-market</td></tr>
<tr><td>Zion Gabay</td><td>Comcast Residential</td></tr>


    </tbody>
</table>

          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  <div class="row">

<div class="col-md-12">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">Case Flow</div>
            <div>
                <button class="btn btn-outline-primary me-2 active" id="btnInFlow" onclick="showChart('inFlow')">In-Flow</button>
                <button class="btn btn-outline-secondary" id="btnOutFlow" onclick="showChart('outFlow')">Out-Flow</button>
            </div>
        </div>
        <div class="card-body pb-0" style="max-height: 400px; overflow-y: auto;">
            <div class="chart-container" id="inFlowChartContainer">
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-container" id="outFlowChartContainer" style="display: none;">
                <canvas id="outFlowChart"></canvas>
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


		      
       
<div class="col-md-12">
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
<div class="col-md-4">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Fix No Fix</div>
        </div>
        <div class="card-body pb-0" style="max-height: 500px; overflow-y: auto;">
            <div class="card-body">
                <div class="chart-container">
                     <canvas id="myDoughnutChart"></canvas>
                </div>
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
	<div class="col-md-8 adjust-position">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Product Line List</h4>
            </div>
            <div class="card-body pb-0" style="height: 400px; overflow-y: auto;">
                <div class="table-responsive">
                    <table id="product-line-table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Product Line</th>
                            </tr>
                        </thead>
                        <tbody>

<tr><td><a href="product.php?name=Amdocs%20AI%20%26%20Data%20Platform">Amdocs AI & Data Platform</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Bill%20Experience">Amdocs Bill Experience</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Care">Amdocs Care</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Catalog">Amdocs Catalog</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Charging">Amdocs Charging</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Commerce">Amdocs Commerce</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Configure%20Price%20Quote%20(CPQ)">Amdocs Configure Price Quote (CPQ)</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Core%20Banking%20Enabler">Amdocs Core Banking Enabler</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Customer%20Engagement">Amdocs Customer Engagement</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Customer%20Engagement%20%E2%80%93%20MS">Amdocs Customer Engagement – MS</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Customer%20Experience%20Suite%20(CES)">Amdocs Customer Experience Suite (CES)</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Data%20Hub">Amdocs Data Hub</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Dynamic%20Document%20Composer">Amdocs Dynamic Document Composer</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Engage">Amdocs Engage</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20eSIM%20Cloud">Amdocs eSIM Cloud</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Low%20Code%20Experience%20Platform">Amdocs Low Code Experience Platform</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Network%20Design">Amdocs Network Design</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Network%20Inventory">Amdocs Network Inventory</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Network%20Orchestration">Amdocs Network Orchestration</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Openet%20Charging">Amdocs Openet Charging</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Order%20Management">Amdocs Order Management</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Real-Time%20Billing">Amdocs Real-Time Billing</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Service%20Activation">Amdocs Service Activation</a></td></tr>
<tr><td><a href="product.php?name=Amdocs%20Service%20Orchestration">Amdocs Service Orchestration</a></td></tr>
<tr><td><a href="product.php?name=AME">AME</a></td></tr>
<tr><td><a href="product.php?name=AMSS">AMSS</a></td></tr>
<tr><td><a href="product.php?name=ARIC">ARIC</a></td></tr>
<tr><td><a href="product.php?name=ARIC%20R1">ARIC R1</a></td></tr>
<tr><td><a href="product.php?name=Billing">Billing</a></td></tr>
<tr><td><a href="product.php?name=Billing%20PCI">Billing PCI</a></td></tr>
<tr><td><a href="product.php?name=Case%20Management">Case Management</a></td></tr>
<tr><td><a href="product.php?name=Content%20RM">Content RM</a></td></tr>
<tr><td><a href="product.php?name=Customer%20Engagement%20Platform">Customer Engagement Platform</a></td></tr>
<tr><td><a href="product.php?name=Customer%20Management">Customer Management</a></td></tr>
<tr><td><a href="product.php?name=Customer%20Service">Customer Service</a></td></tr>
<tr><td><a href="product.php?name=Document%20Designer">Document Designer</a></td></tr>
<tr><td><a href="product.php?name=Enterprise%20Product%20Catalogue">Enterprise Product Catalogue</a></td></tr>
<tr><td><a href="product.php?name=Foundation%20Management">Foundation Management</a></td></tr>
<tr><td><a href="product.php?name=Master%20Enterprise%20Catalog">Master Enterprise Catalog</a></td></tr>
<tr><td><a href="product.php?name=Mediation">Mediation</a></td></tr>
<tr><td><a href="product.php?name=MS360%20Platform">MS360 Platform</a></td></tr>
<tr><td><a href="product.php?name=MS360%20SDK">MS360 SDK</a></td></tr>
<tr><td><a href="product.php?name=OMNI-Channel%20Experience">OMNI-Channel Experience</a></td></tr>
<tr><td><a href="product.php?name=Ordering">Ordering</a></td></tr>
<tr><td><a href="product.php?name=OSS%20Activation">OSS Activation</a></td></tr>
<tr><td><a href="product.php?name=OSS%20ARM%20%26%20Planning">OSS ARM & Planning</a></td></tr>
<tr><td><a href="product.php?name=OSS%20DIM">OSS DIM</a></td></tr>
<tr><td><a href="product.php?name=OSS%20Foundation">OSS Foundation</a></td></tr>
<tr><td><a href="product.php?name=OSS%20Network%20Rollout%20Solutions">OSS Network Rollout Solutions</a></td></tr>
<tr><td><a href="product.php?name=OSS%20Service%20Fulfillment">OSS Service Fulfillment</a></td></tr>
<tr><td><a href="product.php?name=Retail">Retail</a></td></tr>
<tr><td><a href="product.php?name=Sales%20and%20Pricing%20Engines">Sales and Pricing Engines</a></td></tr>
<tr><td><a href="product.php?name=Sanity">Sanity</a></td></tr>
<tr><td><a href="product.php?name=TC">TC</a></td></tr>
<tr><td><a href="product.php?name=UX%20Framework">UX Framework</a></td></tr>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<div class="col-md-12">
    <div class="card">
		<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="card-title">Milestone</h4>
	<p class="card-subtitle">Last Updated: <?php echo $lastUpdateDate ? htmlspecialchars($lastUpdateDate) : '01/01/2025 - 05:22 PM IST'; ?></p>
    <a href="export_milestone.php" class="btn btn-success btn-sm">
        Export to CSV
    </a>
</div>

		
        <div class="card-body">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table id="milestone-table" class="display table table-striped table-hover" style="width: 100%;">
                    <thead class="thead-light">
                        <tr>
                            <th>Effective From</th>
                            <th>MS Name</th>
                            <th>Account Name</th>
                            <th>Account Release</th>
                            <th>Product Line</th>
                            <th>MS Sub Type</th>
                            <th>From PB</th>
                            <th>To PB</th>
                            <th>SAM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Check if there are results and loop through them
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>" . htmlspecialchars($row['EffectiveFrom']) . "</td>
                                        <td>" . htmlspecialchars($row['MilestoneName']) . "</td>
                                        <td>" . htmlspecialchars($row['AccountName']) . "</td>
                                        <td>" . htmlspecialchars($row['AccountRelease']) . "</td>
                                        <td>" . htmlspecialchars($row['ProductLine']) . "</td>
                                        <td>" . htmlspecialchars($row['MilestoneSubType']) . "</td>
                                        <td>" . htmlspecialchars($row['FromPB']) . "</td>
                                        <td>" . htmlspecialchars($row['ToPB']) . "</td>
                                        <td>" . htmlspecialchars($row['SupportAccountManager']) . "</td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>




		</div>


</div>


        <footer class="footer">
          <div class="container-fluid d-flex justify-content-between">

          </div>
        </footer>
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
$(document).ready(function() {
  // Ensure the chatbot is closed by default on page load
  $('#chatbot').hide();

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

      // Send user input to chat.php
      $.ajax({
        url: 'chat.php', // Path to your PHP file
        type: 'POST',
        data: { user_input: userInput },
        success: function (response) {
          // Remove loading animation and append chatbot response
          $('.loading').remove(); 
          $('#chatbot-body').append('<p class="chatbot-message"><strong>Chatbot:</strong> ' + response + '</p>');
          $('#chatbot-body').scrollTop($('#chatbot-body')[0].scrollHeight); // Scroll to the bottom
        },
        error: function () {
          $('.loading').remove(); // Remove loading animation if error occurs
          $('#chatbot-body').append('<p class="chatbot-message"><strong>Chatbot:</strong> Sorry, there was an error processing your request.</p>');
          $('#chatbot-body').scrollTop($('#chatbot-body')[0].scrollHeight);
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
    $(document).ready(function () {
        // Remove empty rows from Product Line List table before initializing DataTable
        $("#product-line-table tbody tr").each(function () {
            var isEmpty = true;
            $(this).find("td").each(function () {
                if ($(this).text().trim() !== "") {
                    isEmpty = false;
                }
            });
            if (isEmpty) {
                $(this).remove(); // Remove blank row
            }
        });

        // Initialize Product Line List Table
        if (!$.fn.dataTable.isDataTable('#product-line-table')) {
            $("#product-line-table").DataTable({
                pageLength: 4, // Fixed number of entries per page
                lengthChange: false, // Hide "Show Entries" dropdown
                searching: true, // Enable search bar
                paging: true, // Enable pagination
                ordering: true, // Enable sorting
                info: true, // Show table info (e.g., "Showing 1-5 of X entries")
            });
        }
    });
</script>

<script>
    $(document).ready(function () {
        // Remove empty rows from Milestone Table before initializing DataTable
        $("#milestone-table tbody tr").each(function () {
            var isEmpty = true;
            $(this).find("td").each(function () {
                if ($(this).text().trim() !== "") {
                    isEmpty = false;
                }
            });
            if (isEmpty) {
                $(this).remove(); // Remove blank row
            }
        });

        // Initialize Milestone Table
        if (!$.fn.dataTable.isDataTable('#milestone-table')) {
            $("#milestone-table").DataTable({
                pageLength: 5, // Fixed number of entries per page
                lengthChange: false, // Hide "Show Entries" dropdown
                searching: true, // Enable search bar
                paging: true, // Enable pagination
                ordering: true, // Enable sorting
                info: true, // Show table info (e.g., "Showing 1-5 of 50 entries")
            });
        }

        // Remove empty rows from other tables before initializing them
        $("#case-data-table tbody tr, #account-table tbody tr").each(function () {
            var isEmpty = true;
            $(this).find("td").each(function () {
                if ($(this).text().trim() !== "") {
                    isEmpty = false;
                }
            });
            if (isEmpty) {
                $(this).remove(); // Remove blank row
            }
        });

        // Initialize other tables as necessary
        if (!$.fn.dataTable.isDataTable('#case-data-table')) {
            $("#case-data-table").DataTable({
                pageLength: 10,
                lengthChange: false, // Hide "Show Entries" dropdown
            });
        }

        if (!$.fn.dataTable.isDataTable('#account-table')) {
            $('#account-table').DataTable({
                pageLength: 5,
                lengthChange: false, // Hide "Show Entries" dropdown
            });
        }
    });
</script>





<script>
    $(document).ready(function () {
        // Initialize multi-filter-select
        if (!$.fn.dataTable.isDataTable('#multi-filter-select')) {
            $("#multi-filter-select").DataTable({
                pageLength: 5,
                initComplete: function () {
                    this.api().columns().every(function () {
                        var column = this;
                        var select = $('<select class="form-control"><option value=""></option></select>')
                            .appendTo($(column.header()).empty())
                            .on("change", function () {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                column.search(val ? "^" + val + "$" : "", true, false).draw();
                            });

                        // Add unique values to the dropdown
                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + "</option>");
                        });
                    });
                },
            });
        }

        // Initialize other tables as necessary
        if (!$.fn.dataTable.isDataTable('#case-data-table')) {
            $("#case-data-table").DataTable({
                pageLength: 10,
            });
        }

        if (!$.fn.dataTable.isDataTable('#account-table')) {
            $('#account-table').DataTable({
                pageLength: 5,
            });
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    var ctx = document.getElementById('lineChart').getContext('2d');
    var barChart = document.getElementById("barChart").getContext("2d");
    var pieChart = document.getElementById("pieChart").getContext("2d");

var myLineChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            datasets: [
                {
                    label: "S1 Cases",
                    borderColor: "#ff6384",
                    pointBorderColor: "#FFF",
                    pointBackgroundColor: "#1d7af3",
                    pointBorderWidth: 2,
                    pointHoverRadius: 4,
                    pointHoverBorderWidth: 1,
                    pointRadius: 4,
                    backgroundColor: "transparent",
                    fill: true,
                    borderWidth: 2,
                    data: [4.2, 3.5, 7.4, 8, 2.9, 6.6]
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: "bottom",
                labels: {
                    padding: 10,
                    fontColor: "#1d7af3"
                }
            },
            tooltips: {
                bodySpacing: 4,
                mode: "nearest",
                intersect: 0,
                position: "nearest",
                xPadding: 10,
                yPadding: 10,
                caretPadding: 10
            },
            layout: {
                padding: {left: 15, right: 15, top: 15, bottom: 15}
            }
        }
    });


    var pieChart = document.getElementById("pieChart").getContext("2d");
    var myPieChart = new Chart(pieChart, {
        type: "pie",
        data: {
            datasets: [{
                data: [60, 40], // Updated data with only 2 values
                backgroundColor: ["#1d7af3", "#f3545d"], // Updated colors to match the number of data points
                borderWidth: 0
            }],
            labels: ["Fix", "No Fix"] // Updated labels to match the number of data points
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: "bottom",
                labels: {
                    fontColor: "rgb(154, 154, 154)",
                    fontSize: 11,
                    usePointStyle: true,
                    padding: 20
                }
            },
            pieceLabel: {
                render: "percentage",
                fontColor: "white",
                fontSize: 14
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
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Parse the case data JSON
    var caseData = <?php echo $caseDataJson; ?>;

    // Get the current date and initialize labels
    var currentDate = new Date();
    var labels = [];
    for (let i = 11; i >= 0; i--) { // For the last 12 months
        let pastDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - i);
        labels.push(pastDate.getFullYear() + '-' + pastDate.toLocaleString('default', { month: 'long' }));
    }

    if (caseData && caseData.length > 0) {
        var s1Data = Array(12).fill(0);
        var s2Data = Array(12).fill(0);
        var s3Data = Array(12).fill(0);
        var s4Data = Array(12).fill(0);

        caseData.forEach(function(caseEntry) {
            var month = caseEntry.Month.trim();
            var severity = caseEntry.Severity;
            var count = parseInt(caseEntry.Count);

            var monthIndex = labels.indexOf(month);
            if (monthIndex > -1) {
                switch (severity) {
                    case "S1":
                        s1Data[monthIndex] = count;
                        break;
                    case "S2":
                        s2Data[monthIndex] = count;
                        break;
                    case "S3":
                        s3Data[monthIndex] = count;
                        break;
                    case "S4":
                        s4Data[monthIndex] = count;
                        break;
                }
            }
        });

        var barChart = document.getElementById("barChart").getContext("2d");
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
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'center', // Place value on the bar
                        color: 'black',
                        font: {
                            weight: 'bold'
                        },
                        formatter: function(value) {
                            return value; // Display the value on the bar
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    } else {
        console.log("No case data found for the specified account.");
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Parse the case data JSON for Out-Flow
    var outFlowData = <?php echo $outFlowDataJson; ?>;

    // Get the current date and initialize labels
    var currentDate = new Date();
    var labels = [];
    for (let i = 11; i >= 0; i--) { // For the last 12 months
        let pastDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - i);
        labels.push(pastDate.getFullYear() + '-' + pastDate.toLocaleString('default', { month: 'long' }));
    }

    if (outFlowData && outFlowData.length > 0) {
        var s1Data = Array(12).fill(0);
        var s2Data = Array(12).fill(0);
        var s3Data = Array(12).fill(0);
        var s4Data = Array(12).fill(0);

        outFlowData.forEach(function(caseEntry) {
            var month = caseEntry.Month.trim();
            var severity = caseEntry.Severity;
            var count = parseInt(caseEntry.Count);

            var monthIndex = labels.indexOf(month);
            if (monthIndex > -1) {
                switch (severity) {
                    case "S1":
                        s1Data[monthIndex] = count;
                        break;
                    case "S2":
                        s2Data[monthIndex] = count;
                        break;
                    case "S3":
                        s3Data[monthIndex] = count;
                        break;
                    case "S4":
                        s4Data[monthIndex] = count;
                        break;
                }
            }
        });

        var outFlowChart = document.getElementById("outFlowChart").getContext("2d");
        var myOutFlowChart = new Chart(outFlowChart, {
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
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'center', // Place value on the bar
                        color: 'black',
                        font: {
                            weight: 'bold'
                        },
                        formatter: function(value) {
                            return value; // Display the value on the bar
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    } else {
        console.log("No Out-Flow data found for the specified account.");
    }
});
</script>
   <style>
    #myDoughnutChart {
        max-width: 500px;
        max-height: 300px;
        width: 80%;
        height: auto;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
    fetch('fetch_data.php')
        .then(response => response.json())
        .then(data => {
            const labels = data.map(item => item.Type);
            const counts = data.map(item => {
                const count = parseInt(item.Total_Count);
                return count > 0 ? count : ''; // Use '' instead of 0
            });

            const ctx = document.getElementById('myDoughnutChart').getContext('2d');
            const myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Count',
                        data: counts,
                        backgroundColor: ['#f9b2b2', '#f4927c', '#75faf2', '#f4e97c'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + (tooltipItem.raw || ''); // Show blank for 0
                                }
                            }
                        },
                        datalabels: {
                            color: '#000',  // Black text
                            backgroundColor: null,  // Remove background
                            borderRadius: 0,
                            padding: 0,
                            font: {
                                weight: 'bold',
                                size: 10  // Reduced font size from 14 to 10
                            },
                            formatter: (value) => {
                                return value || ''; // Return blank for 0
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]  // Enable the Datalabels plugin
            });
        })
        .catch(error => console.error('Error fetching data:', error));
</script>


	
<script>
    // Get the last 12 months including the current month in the format YYYY-Month
    const getLastTwelveMonths = () => {
        const months = [];
        const currentDate = new Date();
        for (let i = 0; i < 12; i++) {
            const month = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
            const monthName = month.toLocaleString('default', { month: 'long' });
            const yearMonth = `${month.getFullYear()}-${monthName}`;
            months.unshift(yearMonth); // Add formatted month to the array
        }
        return months;
    };

    const lastTwelveMonths = getLastTwelveMonths();

    // Fetch data from PHP script
    fetch('fetch.php')
        .then(response => response.json())
        .then(data => {
            // Create a mapping of month to AHT
            const ahtMap = {};
            data.forEach(item => {
                ahtMap[item.Month] = parseInt(item.AHT) || 0; // Default to 0 if AHT is null or not a number
            });

            // Prepare the final data arrays
            const months = lastTwelveMonths;
            const ahtValues = months.map(month => ahtMap[month] || 0); // Default to 0 if month is not found

            // Create the line chart
            const ctx = document.getElementById('myLineChart').getContext('2d');
            const myLineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Average Handling Time (AHT)',
                        data: ahtValues,
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + tooltipItem.raw;
                                }
                            }
                        },
                        datalabels: {
                            color: '#000', // Set text color to black
                            backgroundColor: null, // Remove background color
                            borderRadius: 0,
                            padding: 0,
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            formatter: (value) => {
                                return value;
                            },
                            anchor: 'end',
                            align: 'top'
                        }
                    }
                },
                plugins: [ChartDataLabels] // Add this line to register the datalabels plugin
            });
        })
        .catch(error => console.error('Error fetching data:', error));
</script>


</body>
</html>
  </body>
</html>