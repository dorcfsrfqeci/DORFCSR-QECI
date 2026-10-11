<?php
/**
 * Admin Notification System Setup
 * Initializes the unified user_notification table for admin notifications
 * Access: http://localhost/DORCFSRF/admin_notification_setup.php
 */

header('Content-Type: application/json');

include __DIR__ . '/db_connect.php';
include __DIR__ . '/api/notification_system.php';

$response = [
    'success' => false,
    'steps' => [],
    'errors' => []
];

try {
    // Step 1: Initialize user_notification table
    $response['steps'][] = 'Initializing user_notification table...';
    if (ensureUserNotificationTable($conn)) {
        $response['steps'][] = '✓ user_notification table created/verified';
    } else {
        $response['errors'][] = 'Failed to create user_notification table';
    }
    
    // Step 2: Verify table structure
    $response['steps'][] = 'Verifying table structure...';
    $tableInfo = $conn->query("DESC user_notification");
    if ($tableInfo && $tableInfo->num_rows > 0) {
        $response['steps'][] = '✓ Table structure verified: ' . $tableInfo->num_rows . ' columns found';
    } else {
        $response['errors'][] = 'Failed to verify table structure';
    }
    
    // Step 3: Check for existing admin users
    $response['steps'][] = 'Checking for admin users...';
    $adminResult = $conn->query("SELECT COUNT(*) as count FROM user_database WHERE role = 'Admin'");
    $adminRow = $adminResult->fetch_assoc();
    $adminCount = $adminRow['count'] ?? 0;
    $response['steps'][] = "✓ Found $adminCount admin user(s)";
    
    // Step 4: Attempt migration if any admin notifications exist
    if ($adminCount > 0) {
        $response['steps'][] = 'Attempting to migrate existing admin notifications...';
        $migrationResult = migrateAdminNotifications($conn);
        if ($migrationResult['success']) {
            $response['steps'][] = "✓ Migration completed: " . $migrationResult['migrated'] . " notifications migrated";
        } else {
            $response['errors'][] = "Migration warning: " . ($migrationResult['error'] ?? 'Unknown error');
        }
    }
    
    // Step 5: Create a test notification
    $response['steps'][] = 'Creating test notification...';
    if ($adminCount > 0) {
        $adminUserResult = $conn->query("SELECT user_id FROM user_database WHERE role = 'Admin' LIMIT 1");
        $adminUser = $adminUserResult->fetch_assoc();
        if ($adminUser) {
            $testAdmin = $adminUser['user_id'];
            $testMessage = 'System: Admin notification system initialized successfully at ' . date('Y-m-d H:i:s');
            
            // Direct insert to test notification creation
            $stmt = $conn->prepare("INSERT INTO user_notification (user_id, recipient_role, notification_type, message) VALUES (?, 'Admin', 'system', ?)");
            if ($stmt) {
                $stmt->bind_param('ss', $testAdmin, $testMessage);
                if ($stmt->execute()) {
                    $response['steps'][] = "✓ Test notification created for $testAdmin";
                    $stmt->close();
                } else {
                    $response['errors'][] = 'Failed to create test notification: ' . $stmt->error;
                    $stmt->close();
                }
            } else {
                $response['errors'][] = 'Failed to prepare statement: ' . $conn->error;
            }
        }
    }
    
    // Step 6: Verify notification retrieval
    $response['steps'][] = 'Verifying notification retrieval...';
    if ($adminCount > 0 && isset($testAdmin)) {
        $testNotifications = getAdminNotifications($conn, $testAdmin, 5);
        $response['steps'][] = "✓ Retrieved " . count($testNotifications) . " notification(s)";
    }
    
    // Final status
    if (count($response['errors']) === 0) {
        $response['success'] = true;
        $response['message'] = 'Admin notification system initialized successfully!';
    } else {
        $response['message'] = 'Admin notification system initialized with warnings';
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['errors'][] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_PRETTY_PRINT);
?>
