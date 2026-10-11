<?php
// Database connection settings for XAMPP
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "clearance_system_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prevent browser caching of pages to ensure logged-out users cannot use the Back button
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Single-device login validation
// Toggle to enable/disable single-device login validation.
// Set to true to enforce single-device only; set to false to allow multiple devices.
$ENFORCE_SINGLE_DEVICE = false;

if ($ENFORCE_SINGLE_DEVICE) {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $current_session_token = $_SESSION['session_token'] ?? null;
        
        $stmt_sd = $conn->prepare("SELECT session_token FROM user_database WHERE user_id = ?");
        if ($stmt_sd) {
            $stmt_sd->bind_param("s", $user_id);
            $stmt_sd->execute();
            $result_sd = $stmt_sd->get_result();
            if ($result_sd && $result_sd->num_rows === 1) {
                $row_sd = $result_sd->fetch_assoc();
                
                // Kick out if token is missing or doesn't match
                if (empty($row_sd['session_token']) || $row_sd['session_token'] !== $current_session_token || $current_session_token === null) {
                    session_unset();
                    session_destroy();
                    header("Location: /DORCFSRF/pages/login_form/index.php?error=device_conflict");
                    exit;
                } else {
                    // Update last_active so we know this session is still alive
                    $update_active = $conn->prepare("UPDATE user_database SET last_active = CURRENT_TIMESTAMP WHERE user_id = ?");
                    if ($update_active) {
                        $update_active->bind_param("s", $user_id);
                        $update_active->execute();
                        $update_active->close();
                    }
                }
            }
            $stmt_sd->close();
        }
    }
}
?>
