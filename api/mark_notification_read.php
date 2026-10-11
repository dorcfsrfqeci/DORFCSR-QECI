<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

include __DIR__ . '/../db_connect.php';
include __DIR__ . '/../api/notification_system.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Notification ID required']);
    exit;
}

$notification_id = intval($_GET['id']);
$role = $_SESSION['role'] ?? '';

if ($role === 'Student') {
    ensureStudentNotificationsTable($conn);
    markStudentNotificationAsRead($conn, $notification_id);
} else {
    // Prefer unified user_notification table
    ensureUserNotificationTable($conn);
    if ($role === 'Admin') {
        markAdminNotificationAsRead($conn, $notification_id);
    } elseif ($role === 'Staff') {
        markStaffNotificationAsReadUnified($conn, $notification_id);
    } else {
        // Fallback to legacy table
        ensureNotificationsTable($conn);
        markNotificationAsRead($conn, $notification_id);
    }
}

echo json_encode(['success' => true]);
?>