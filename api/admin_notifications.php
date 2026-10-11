<?php
/**
 * Admin Notifications API
 * Handles getting, creating, and managing admin notifications
 */

ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

include __DIR__ . '/../db_connect.php';
include __DIR__ . '/../api/notification_system.php';

// Check authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Admin access required']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

try {
    switch ($action) {
        case 'get':
            // Get admin notifications
            $limit = intval($_GET['limit'] ?? 10);
            ensureUserNotificationTable($conn);
            
            $unreadCount = getAdminUnreadCount($conn, $admin_id);
            $notifications = getAdminNotifications($conn, $admin_id, $limit);
            
            $response = [
                'success' => true,
                'unreadCount' => $unreadCount,
                'notifications' => []
            ];
            
            foreach ($notifications as $notif) {
                $response['notifications'][] = [
                    'id' => $notif['id'],
                    'student_name' => htmlspecialchars($notif['student_name'] ?? ''),
                    'message' => htmlspecialchars($notif['message']),
                    'notification_type' => $notif['notification_type'],
                    'created_at' => date('M d, Y g:ia', strtotime($notif['created_at'])),
                    'is_read' => (bool) $notif['is_read']
                ];
            }
            
            echo json_encode($response);
            break;
            
        case 'mark_read':
            // Mark notification as read
            $notification_id = intval($_POST['notification_id'] ?? 0);
            
            if ($notification_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
                exit;
            }
            
            ensureUserNotificationTable($conn);
            $result = markAdminNotificationAsRead($conn, $notification_id);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]);
            break;
            
        case 'create':
            // Create admin notification (system use)
            $message = $_POST['message'] ?? '';
            $type = $_POST['type'] ?? 'status_update';
            $student_id = $_POST['student_id'] ?? null;
            $student_name = $_POST['student_name'] ?? null;
            
            if (empty($message)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Message is required']);
                exit;
            }
            
            ensureUserNotificationTable($conn);
            $result = createUnifiedNotification(
                $conn,
                $admin_id,
                'Admin',
                $message,
                $type,
                null,
                $student_id,
                $student_name
            );
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification created' : 'Failed to create notification'
            ]);
            break;
            
        case 'delete':
            // Delete notification
            $notification_id = intval($_POST['notification_id'] ?? 0);
            
            if ($notification_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
                exit;
            }
            
            ensureUserNotificationTable($conn);
            $result = deleteUnifiedNotification($conn, $notification_id);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification deleted' : 'Failed to delete notification'
            ]);
            break;
            
        case 'init':
            // Initialize the table
            ensureUserNotificationTable($conn);
            echo json_encode(['success' => true, 'message' => 'Admin notification table initialized']);
            break;
            
        case 'migrate':
            // Migrate existing admin notifications to unified table
            $migrationResult = migrateAdminNotifications($conn);
            echo json_encode($migrationResult);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
