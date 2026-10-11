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

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

$response = [
    'unreadCount' => 0,
    'notifications' => []
];

if ($role === 'Student') {
    ensureStudentNotificationsTable($conn);
    $response['unreadCount'] = getUnreadStudentNotificationCount($conn, $user_id);
    $notifications = getStudentNotifications($conn, $user_id, 10);

    foreach ($notifications as $notif) {
        $response['notifications'][] = [
            'id' => $notif['id'],
            'message' => htmlspecialchars($notif['message']),
            'created_at' => date('M d, Y g:ia', strtotime($notif['created_at'])),
            'is_read' => (bool) $notif['is_read']
        ];
    }
} else {
    // Prefer the unified user_notification table for Admin and Staff
    ensureUserNotificationTable($conn);
    if ($role === 'Admin') {
        $response['unreadCount'] = getAdminUnreadCount($conn, $user_id);
        $notifications = getAdminNotifications($conn, $user_id, 10);
    } elseif ($role === 'Staff') {
        $response['unreadCount'] = getStaffUnreadCountUnified($conn, $user_id);
        $notifications = getStaffNotificationsUnified($conn, $user_id, 10);
    } else {
        // Fallback to legacy staff_notifications for any other roles
        ensureNotificationsTable($conn);
        $response['unreadCount'] = getUnreadNotificationCount($conn, $user_id);
        $notifications = getNotifications($conn, $user_id, 5);
    }

    foreach ($notifications as $notif) {
        $response['notifications'][] = [
            'id' => $notif['id'],
            'student_name' => htmlspecialchars($notif['student_name'] ?? ''),
            'message' => htmlspecialchars($notif['message'] ?? ''),
            'created_at' => date('M d, Y g:ia', strtotime($notif['created_at'] ?? null)),
            'is_read' => (bool) ($notif['is_read'] ?? false)
        ];
    }
}

echo json_encode($response);
?>