<?php
/**
 * Unified Notification System Setup & Migration
 * Migrates all existing staff and student notifications to user_notification table
 * Access: http://localhost/DORCFSRF/unified_notification_setup.php
 */

header('Content-Type: application/json');

include __DIR__ . '/db_connect.php';
include __DIR__ . '/api/notification_system.php';

$response = [
    'success' => false,
    'steps' => [],
    'errors' => [],
    'migration_summary' => []
];

try {
    // Step 1: Initialize unified table
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
    
    // Step 3: Check for existing admins
    $response['steps'][] = 'Checking user roles...';
    $adminResult = $conn->query("SELECT COUNT(*) as count FROM user_database WHERE role = 'Admin'");
    $adminRow = $adminResult->fetch_assoc();
    $adminCount = $adminRow['count'] ?? 0;
    
    $staffResult = $conn->query("SELECT COUNT(*) as count FROM user_database WHERE role = 'Staff'");
    $staffRow = $staffResult->fetch_assoc();
    $staffCount = $staffRow['count'] ?? 0;
    
    $studentResult = $conn->query("SELECT COUNT(*) as count FROM user_database WHERE role = 'Student'");
    $studentRow = $studentResult->fetch_assoc();
    $studentCount = $studentRow['count'] ?? 0;
    
    $response['steps'][] = "✓ Found $adminCount admin(s), $staffCount staff, $studentCount student(s)";
    
    // Step 4: Migrate Admin notifications
    $response['steps'][] = 'Migrating admin notifications...';
    $adminMigration = migrateAdminNotifications($conn);
    if ($adminMigration['success']) {
        $response['steps'][] = "✓ Admin migration: " . $adminMigration['migrated'] . " notification(s) migrated";
        $response['migration_summary']['admin_migrations'] = $adminMigration['migrated'];
    } else {
        $response['errors'][] = "Admin migration: " . ($adminMigration['error'] ?? 'Unknown error');
    }
    
    // Step 5: Migrate Staff notifications
    $response['steps'][] = 'Migrating staff notifications...';
    $staffMigration = migrateStaffNotifications($conn);
    if ($staffMigration['success']) {
        $response['steps'][] = "✓ Staff migration: " . $staffMigration['migrated'] . " notification(s) migrated";
        $response['migration_summary']['staff_migrations'] = $staffMigration['migrated'];
    } else {
        $response['errors'][] = "Staff migration: " . ($staffMigration['error'] ?? 'Unknown error');
    }
    
    // Step 6: Migrate Student notifications
    $response['steps'][] = 'Migrating student notifications...';
    $studentMigration = migrateStudentNotifications($conn);
    if ($studentMigration['success']) {
        $response['steps'][] = "✓ Student migration: " . $studentMigration['migrated'] . " notification(s) migrated";
        $response['migration_summary']['student_migrations'] = $studentMigration['migrated'];
    } else {
        $response['errors'][] = "Student migration: " . ($studentMigration['error'] ?? 'Unknown error');
    }
    
    // Step 7: Create test notifications
    $response['steps'][] = 'Creating test notifications for all roles...';
    $testSuccess = true;
    
    // Test admin notification
    if ($adminCount > 0) {
        $adminUserResult = $conn->query("SELECT user_id FROM user_database WHERE role = 'Admin' LIMIT 1");
        $adminUser = $adminUserResult->fetch_assoc();
        if ($adminUser) {
            $testAdmin = $adminUser['user_id'];
            $stmt = $conn->prepare("INSERT INTO user_notification (user_id, recipient_role, notification_type, message) VALUES (?, 'Admin', 'system', ?)");
            $testMsg = 'System: Unified notification system initialized at ' . date('Y-m-d H:i:s');
            $stmt->bind_param('ss', $testAdmin, $testMsg);
            if ($stmt->execute()) {
                $response['steps'][] = "✓ Test notification created for Admin";
                $stmt->close();
            } else {
                $testSuccess = false;
            }
        }
    }
    
    // Test staff notification
    if ($staffCount > 0) {
        $staffUserResult = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff' LIMIT 1");
        $staffUser = $staffUserResult->fetch_assoc();
        if ($staffUser) {
            $testStaff = $staffUser['user_id'];
            $stmt = $conn->prepare("INSERT INTO user_notification (user_id, recipient_role, notification_type, message) VALUES (?, 'Staff', 'system', ?)");
            $testMsg = 'System: Unified notification system initialized at ' . date('Y-m-d H:i:s');
            $stmt->bind_param('ss', $testStaff, $testMsg);
            if ($stmt->execute()) {
                $response['steps'][] = "✓ Test notification created for Staff";
                $stmt->close();
            } else {
                $testSuccess = false;
            }
        }
    }
    
    // Test student notification
    if ($studentCount > 0) {
        $studentUserResult = $conn->query("SELECT user_id FROM user_database WHERE role = 'Student' LIMIT 1");
        $studentUser = $studentUserResult->fetch_assoc();
        if ($studentUser) {
            $testStudent = $studentUser['user_id'];
            $stmt = $conn->prepare("INSERT INTO user_notification (user_id, recipient_role, notification_type, message) VALUES (?, 'Student', 'system', ?)");
            $testMsg = 'System: Unified notification system initialized at ' . date('Y-m-d H:i:s');
            $stmt->bind_param('ss', $testStudent, $testMsg);
            if ($stmt->execute()) {
                $response['steps'][] = "✓ Test notification created for Student";
                $stmt->close();
            } else {
                $testSuccess = false;
            }
        }
    }
    
    // Step 8: Verify counts
    $response['steps'][] = 'Verifying notification counts...';
    $totalNotifs = $conn->query("SELECT COUNT(*) as count FROM user_notification");
    $totalRow = $totalNotifs->fetch_assoc();
    $totalCount = $totalRow['count'] ?? 0;
    
    $response['steps'][] = "✓ Total notifications in user_notification: $totalCount";
    
    // Final status
    if (count($response['errors']) === 0) {
        $response['success'] = true;
        $response['message'] = 'Unified notification system initialized successfully!';
    } else {
        $response['message'] = 'Unified notification system initialized with warnings';
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['errors'][] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_PRETTY_PRINT);
?>
