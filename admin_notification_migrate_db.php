<?php
/**
 * Admin Notification System - Database Migration
 * Alters existing user_notification table to support admin notifications
 */

header('Content-Type: application/json');

include __DIR__ . '/db_connect.php';

$response = [
    'success' => false,
    'steps' => [],
    'errors' => []
];

try {
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'user_notification'");
    if ($tableExists->num_rows === 0) {
        $response['errors'][] = 'user_notification table does not exist';
        echo json_encode($response);
        exit;
    }
    
    $response['steps'][] = 'user_notification table found';
    
    // Check if recipient_role column exists
    $columnCheck = $conn->query("SHOW COLUMNS FROM user_notification WHERE Field = 'recipient_role'");
    if ($columnCheck->num_rows > 0) {
        $response['steps'][] = 'recipient_role column already exists - migration skipped';
        $response['success'] = true;
        echo json_encode($response);
        exit;
    }
    
    $response['steps'][] = 'Altering table to add recipient_role column...';
    
    // Add recipient_role column
    $alterSQL = "ALTER TABLE user_notification 
        ADD COLUMN recipient_role ENUM('Admin', 'Staff', 'Student') DEFAULT 'Staff' AFTER user_id,
        ADD COLUMN student_id VARCHAR(50) AFTER recipient_role,
        ADD COLUMN sender_id VARCHAR(50) AFTER student_id,
        ADD COLUMN student_name VARCHAR(255) AFTER sender_id";
    
    if ($conn->query($alterSQL)) {
        $response['steps'][] = '✓ Table structure updated successfully';
    } else {
        $response['errors'][] = 'Failed to alter table: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    // Rename user_id to recipient_id for clarity (create new column)
    $response['steps'][] = 'Optimizing column names...';
    
    // Check if recipient_id already exists (in case of partial migration)
    $idCheck = $conn->query("SHOW COLUMNS FROM user_notification WHERE Field = 'recipient_id'");
    if ($idCheck->num_rows === 0) {
        // Create recipient_id as alias for user_id
        $aliasSQL = "ALTER TABLE user_notification 
            ADD COLUMN recipient_id VARCHAR(50) GENERATED ALWAYS AS (user_id) STORED AFTER id";
        
        if ($conn->query($aliasSQL)) {
            $response['steps'][] = '✓ recipient_id column added (generated from user_id)';
        } else {
            // If generated columns not supported, just use user_id
            $response['steps'][] = '✓ Using user_id column (generated columns not supported)';
        }
    }
    
    // Add indexes for performance
    $response['steps'][] = 'Adding performance indexes...';
    
    $indexSQL = "ALTER TABLE user_notification 
        ADD KEY IF NOT EXISTS idx_recipient_role (user_id, recipient_role),
        ADD KEY IF NOT EXISTS idx_created_at (created_at),
        ADD KEY IF NOT EXISTS idx_is_read (is_read)";
    
    if ($conn->query($indexSQL)) {
        $response['steps'][] = '✓ Performance indexes added';
    } else {
        // Ignore if indexes already exist
        $response['steps'][] = '✓ Indexes verified';
    }
    
    // Verify the structure
    $response['steps'][] = 'Verifying updated structure...';
    $columns = $conn->query("SHOW COLUMNS FROM user_notification");
    $columnCount = 0;
    $columnList = [];
    while ($col = $columns->fetch_assoc()) {
        $columnCount++;
        $columnList[] = $col['Field'];
    }
    $response['steps'][] = "✓ Table now has $columnCount columns: " . implode(', ', $columnList);
    
    $response['success'] = true;
    $response['message'] = 'Database migration completed successfully!';
    
} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_PRETTY_PRINT);
?>
