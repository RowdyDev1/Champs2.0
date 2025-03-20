<?php
// Define API endpoints and headers
$run_api_url = "https://agents.autox.corp.amdocs.azr/api/v1/agents/run";
$status_api_url = "https://agents.autox.corp.amdocs.azr/api/v1/agents/status/";
$headers = [
    "accept: application/json",
    "X-API-Key: b87a4b68-a65d-4f77-9bb8-9d1b81693519",
    "Content-Type: application/json"
];

// Function to send user input to chatbot and get task ID
function send_message($user_input) {
    global $run_api_url, $headers;

    // Append JSON format request
    $user_input .= " show reply of this message in json format";

    $data = [
        "username" => "azadm",
        "message" => $user_input,
        "agent_type" => "csv",
        "data_filenames" => ["12.csv"]
    ];

    $ch = curl_init($run_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $response_data = json_decode($response, true);
    
    return $response_data['task_id'] ?? null;
}

// Function to format chatbot response as a table
function format_response($response) {
    $data = json_decode($response, true);

    if (is_array($data) && isset($data[0])) {
        // If response is an array of objects, format as a table
        return format_cases($data);
    } elseif (json_last_error() == JSON_ERROR_NONE) {
        // If response is a JSON object, format it properly
        $formatted_response = "<div style='font-size: 16px; color: #333; background: #f9f9f9; padding: 10px; border-radius: 5px;'>";
        $formatted_response .= "<strong>Chatbot Response:</strong><br><ul>";

        foreach ($data as $key => $value) {
            $formatted_response .= "<li><strong>" . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . ":</strong> " . htmlspecialchars($value) . "</li>";
        }

        $formatted_response .= "</ul></div>";
        return $formatted_response;
    } elseif (is_string($response)) {
        // If it's just plain text, return it as is
        return "<div style='font-size: 16px; color: #333;'>" . nl2br(htmlspecialchars($response)) . "</div>";
    }

    return "<div style='color: red; font-weight: bold;'>⚠️ Unexpected response format.</div>";
}


function get_chatbot_response($task_id, $save_csv = false) {
    global $status_api_url, $headers;
    
    $status_url = $status_api_url . $task_id;
    
    while (true) {
        $status_ch = curl_init($status_url);
        curl_setopt($status_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($status_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($status_ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $status_response = curl_exec($status_ch);
        curl_close($status_ch);
        
        $status_data = json_decode($status_response, true);
        
        if (isset($status_data['status']) && $status_data['status'] === "Complete") {
            $result = $status_data['result'];

            if (is_string($result)) {
                preg_match('/\[(\{.*\})\]/', $result, $matches);
                if (!empty($matches[0]) && isJson($matches[0])) {
                    $result = json_decode($matches[0], true);
                }
            }

            $formatted_response = "";

            if (is_string($result)) {
                $formatted_response = nl2br(htmlspecialchars($result));
            } elseif (is_array($result) && isset($result[0])) {
                $formatted_response = format_cases($result);
            } else {
                $formatted_response = "⚠️ Unexpected response format.";
            }

            // Generate a unique filename for each output file
            $unique_filename = "output_" . date("Ymd_His") . ".txt";
            $file_path = __DIR__ . "/" . $unique_filename;

            // Format content before saving
            $formatted_for_file = "Formatted Response:\n\n" . strip_tags($formatted_response) . "\n\nEnd of Response";

            // Save formatted content to the unique output file
            file_put_contents($file_path, $formatted_for_file);

            // Provide a download link for the unique file
            $download_link = "<br><br><a href='$unique_filename' download='$unique_filename' style='color: blue; font-weight: bold;'>📥 Download Output File</a>";

            return $formatted_response . $download_link;
        }
        
        sleep(3);
    }
}






// Helper function to check if a string is valid JSON
function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

// Function to format case details in a styled block
function format_cases($cases) {
    $output = "<div style='font-family: Arial, sans-serif; color: #333; font-size: 14px;'>";

    foreach ($cases as $case) {
        $output .= "<div style='border-left: 5px solid #007BFF; padding: 10px; margin: 10px 0; background: #f9f9f9; border-radius: 5px;'>
                        <strong>📌 Case ID:</strong> <span style='color: #007BFF;'>" . htmlspecialchars($case['Case ID']) . "</span><br>
                        <strong>🔹 Case Type:</strong> " . htmlspecialchars($case['Case Type']) . "<br>
                        <strong>🏷️ Instance Name:</strong> " . htmlspecialchars($case['Case Instance Name']) . "<br>
                        <strong>⚠️ Severity:</strong> <span style='color: " . getSeverityColor($case['Case Severity']) . ";'>" . htmlspecialchars($case['Case Severity']) . "</span><br>
                        <strong>🏢 Account Name:</strong> " . htmlspecialchars($case['Case Account Name']) . "<br>
                        <strong>🛠 Resolved With/Without Fix:</strong> " . htmlspecialchars($case['Resolved With/Without Fix']) . "
                    </div>";
    }

    $output .= "</div>";
    return $output;
}

// Function to assign colors to severity levels
function getSeverityColor($severity) {
    switch ($severity) {
        case "S1": return "#D9534F"; // Red for critical
        case "S2": return "#F0AD4E"; // Orange for high
        case "S3": return "#5BC0DE"; // Blue for medium
        default: return "#5A5A5A"; // Grey for others
    }
}

// Function to save JSON data to CSV
function save_to_csv($data) {
    $folder_path = "C:\\xampp\\htdocs\\champsuat";
    if (!file_exists($folder_path)) {
        mkdir($folder_path, 0777, true);
    }

    $filename = $folder_path . "\\output_" . date("Ymd_His") . ".csv";
    $fp = fopen($filename, 'w');

    if (!empty($data)) {
        fputcsv($fp, array_keys($data[0])); // CSV header

        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
    }

    fclose($fp);
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_input'])) {
    $user_input = trim($_POST['user_input']);
    
    if (!empty($user_input)) {
        $task_id = send_message($user_input);
        
        if ($task_id) {
            $save_csv = stripos($user_input, "CSV") !== false; // Check if user wants CSV
            echo get_chatbot_response($task_id, $save_csv);
        } else {
            echo "<div style='color: red; font-weight: bold;'>❌ Error: Task ID not received.</div>";
        }
    } else {
        echo "<div style='color: orange; font-weight: bold;'>⚠️ Invalid input.</div>";
    }
}
?>