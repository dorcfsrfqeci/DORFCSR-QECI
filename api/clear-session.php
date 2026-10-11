<?php
/**
 * Clear Session API
 * Called when user navigates away from application
 */

header('Content-Type: application/json');

include __DIR__ . '/../db_connect.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['session_token'])) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    $user_id = trim($data['user_id']);
    $session_token = trim($data['session_token']);

    // Clear the session token for this user
    $stmt = $conn->prepare("UPDATE user_database SET session_token = NULL WHERE user_id = ? AND session_token = ?");
    $stmt->bind_param('ss', $user_id, $session_token);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Session cleared']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear session']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
