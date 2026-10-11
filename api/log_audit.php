<?php
function log_activity($conn, $user_id, $user_name, $role, $module, $action, $status = 'Success', $record_type = null, $record_id = null) {
    // Generate unique audit ID
    $audit_id = 'AUD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Get basic device info from user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $device_info = 'Unknown Device';
    if (preg_match('/Windows NT 11.0/i', $user_agent) || preg_match('/Windows NT 10.0/i', $user_agent)) $device_info = 'Windows 10/11';
    elseif (preg_match('/Windows NT 6.3/i', $user_agent)) $device_info = 'Windows 8.1';
    elseif (preg_match('/Windows NT 6.2/i', $user_agent)) $device_info = 'Windows 8';
    elseif (preg_match('/Windows NT 6.1/i', $user_agent)) $device_info = 'Windows 7';
    elseif (preg_match('/Mac OS X/i', $user_agent)) $device_info = 'Mac OS';
    elseif (preg_match('/Linux/i', $user_agent)) $device_info = 'Linux';
    elseif (preg_match('/Android/i', $user_agent)) $device_info = 'Android';
    elseif (preg_match('/iPhone|iPad|iPod/i', $user_agent)) $device_info = 'iOS';

    // Get Browser
    if (preg_match('/Chrome/i', $user_agent) && !preg_match('/Edge/i', $user_agent)) $device_info .= ' - Chrome';
    elseif (preg_match('/Firefox/i', $user_agent)) $device_info .= ' - Firefox';
    elseif (preg_match('/Safari/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) $device_info .= ' - Safari';
    elseif (preg_match('/Edge/i', $user_agent)) $device_info .= ' - Edge';
    else $device_info .= ' - Browser';

    $stmt = $conn->prepare("INSERT INTO audit_logs (audit_id, user_id, user_name, role, module, action, status, record_type, record_id, ip_address, device_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssssssssss", $audit_id, $user_id, $user_name, $role, $module, $action, $status, $record_type, $record_id, $ip_address, $device_info);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}
?>
