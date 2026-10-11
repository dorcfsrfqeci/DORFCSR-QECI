<?php
/**
 * Logout Script
 * Destroys the session and redirects to login page
 */

ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session token and reset last_active from database
if (isset($_SESSION['user_id'])) {
    include __DIR__ . '/../db_connect.php';
    include_once __DIR__ . '/../api/log_audit.php';
    if ($conn && !$conn->connect_error) {
        log_activity($conn, $_SESSION['user_id'], $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User', $_SESSION['role'] ?? 'Unknown', 'System', 'Logout', 'Success', 'Auth', 'SESSION');
        // Properly clear the session token AND reset last_active so user can login again
        $clear_stmt = $conn->prepare("UPDATE user_database SET session_token = NULL, last_active = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE user_id = ?");
        $clear_stmt->bind_param("s", $_SESSION['user_id']);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        // Verify the update was successful
        $verify_stmt = $conn->prepare("SELECT session_token FROM user_database WHERE user_id = ?");
        $verify_stmt->bind_param("s", $_SESSION['user_id']);
        $verify_stmt->execute();
        $verify_stmt->close();
        
        $conn->close();
    }
}

// Destroy all session data
session_destroy();

// Display logout message with localStorage clearing
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <script>
        // Clear authentication token from localStorage
        localStorage.removeItem("userToken");
        localStorage.removeItem("userRole");
        localStorage.removeItem("loginTime");
        
        // Clear sessionStorage as well
        sessionStorage.clear();
        
        // Redirect to login page after clearing
        setTimeout(function() {
            window.location.href = '/DORCFSRF/pages/login_form/index.php';
        }, 100);
    </script>
    <p>Logging out...</p>
</body>
</html>
