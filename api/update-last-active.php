<?php
/**
 * Update Last Active API
 * Called periodically to keep user session active
 */

header('Content-Type: application/json');

include __DIR__ . '/../db_connect.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing user_id']);
        exit;
    }

    $user_id = trim($data['user_id']);

    // Update the last_active timestamp
    $stmt = $conn->prepare("UPDATE user_database SET last_active = CURRENT_TIMESTAMP WHERE user_id = ?");
    $stmt->bind_param('s', $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Last active updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
