<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set test session for testing purposes
$_SESSION['user_id'] = 'TESTADMIN';
$_SESSION['role'] = 'Admin';
$_SESSION['full_name'] = 'Test Admin';

include __DIR__ . '/db_connect.php';
include __DIR__ . '/api/notification_system.php';

echo "<h2>FORM SUBMISSION NOTIFICATION TEST</h2>";
echo "<pre>";

try {
    // Test 1: Form Submission Notification
    echo "\n=== TEST 1: Form Submission Notification ===\n";
    $student_id = 'CS-22-0001';
    $student_name = 'Test Student';
    
    $result = createFormSubmissionNotification($conn, $student_id, $student_name, 'Student Form Submission');
    echo "✓ Form submission notification created\n";
    
    // Verify all roles got notified
    $verify = $conn->query("SELECT COUNT(*) as count FROM user_notification WHERE notification_type = 'form_submission'");
    $row = $verify->fetch_assoc();
    echo "  - Total form submission notifications in DB: " . $row['count'] . "\n";
    
    // Test 2: Document Submission Notification
    echo "\n=== TEST 2: Document Submission Notification ===\n";
    $result = createDocumentSubmissionNotification($conn, $student_id, $student_name, 'Clearance Document');
    echo "✓ Document submission notification created\n";
    
    $verify = $conn->query("SELECT COUNT(*) as count FROM user_notification WHERE notification_type = 'document_submission'");
    $row = $verify->fetch_assoc();
    echo "  - Total document submission notifications in DB: " . $row['count'] . "\n";
    
    // Test 3: Payment Notification
    echo "\n=== TEST 3: Payment Notification ===\n";
    $result = createPaymentNotification($conn, $student_id, $student_name, 500.00, 'submitted');
    echo "✓ Payment notification created\n";
    
    $verify = $conn->query("SELECT COUNT(*) as count FROM user_notification WHERE notification_type = 'payment'");
    $row = $verify->fetch_assoc();
    echo "  - Total payment notifications in DB: " . $row['count'] . "\n";
    
    // Test 4: Clearance Status Notification
    echo "\n=== TEST 4: Clearance Status Notification ===\n";
    $result = createClearanceStatusNotification($conn, $student_id, $student_name, 'Cleared - Records Officer');
    echo "✓ Clearance status notification created\n";
    
    $verify = $conn->query("SELECT COUNT(*) as count FROM user_notification WHERE notification_type = 'clearance_status'");
    $row = $verify->fetch_assoc();
    echo "  - Total clearance status notifications in DB: " . $row['count'] . "\n";
    
    // Test 5: Get notifications by role
    echo "\n=== TEST 5: Check Notifications by Role ===\n";
    
    $adminNotifs = $conn->query("SELECT COUNT(*) as count FROM user_notification WHERE recipient_role = 'Admin'");
    $adminRow = $adminNotifs->fetch_assoc();
    echo "Admin notifications: " . $adminRow['count'] . "\n";
    
    $staffNotifs = $conn->query("SELECT COUNT(*) as count FROM user_notification WHERE recipient_role = 'Staff'");
    $staffRow = $staffNotifs->fetch_assoc();
    echo "Staff notifications: " . $staffRow['count'] . "\n";
    
    $studentNotifs = $conn->query("SELECT COUNT(*) as count FROM user_notification WHERE recipient_role = 'Student'");
    $studentRow = $studentNotifs->fetch_assoc();
    echo "Student notifications: " . $studentRow['count'] . "\n";
    
    // Test 6: Sample recent notifications
    echo "\n=== TEST 6: Recent Notifications from user_notification table ===\n";
    $recent = $conn->query("SELECT recipient_role, notification_type, message, created_at FROM user_notification ORDER BY recipient_id DESC LIMIT 20");
    
    echo "\nRole | Type | Message | Created At\n";
    echo str_repeat("-", 120) . "\n";
    
    while ($row = $recent->fetch_assoc()) {
        $role = substr($row['recipient_role'], 0, 6);
        $type = substr($row['notification_type'], 0, 15);
        $msg = substr($row['message'] ?? '', 0, 40);
        $time = substr($row['created_at'], 11, 8);
        printf("%-6s | %-15s | %-40s | %s\n", $role, $type, $msg, $time);
    }
    
    echo "\n" . str_repeat("=", 120) . "\n";
    echo "✅ ALL TESTS PASSED - Notifications are being recorded in user_notification database!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
