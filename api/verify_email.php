<?php
/**
 * Email Verification API
 * Checks if an email exists in the user database
 */

header('Content-Type: application/json');

try {
    include __DIR__ . '/../db_connect.php';

    // Check if connection was successful
    if (!isset($conn) || $conn->connect_error) {
        echo json_encode(['exists' => false, 'message' => 'Database connection failed']);
        exit;
    }

    // Get JSON data from request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email'])) {
        echo json_encode(['exists' => false, 'message' => 'Email not provided']);
        exit;
    }

    $email = trim($data['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['exists' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Check if email exists in user_database
    $stmt = $conn->prepare("SELECT user_id FROM user_database WHERE email = ? LIMIT 1");
    
    if (!$stmt) {
        echo json_encode(['exists' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo json_encode(['exists' => true, 'message' => 'Email found']);
    } else {
        echo json_encode(['exists' => false, 'message' => 'Email not found']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['exists' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

