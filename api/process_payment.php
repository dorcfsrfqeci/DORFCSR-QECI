<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

include __DIR__ . '/../db_connect.php';
include __DIR__ . '/../api/PaymentProcessor.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit;
}

try {
    $processor = new PaymentProcessor($conn);

    // Validate payment data
    $validation = $processor->validatePayment($data['amount'] ?? 0, $data['method'] ?? '');
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'message' => $validation['error']]);
        exit;
    }

    // Process payment (currently simulation)
    $result = $processor->processPayment($data);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'transaction_id' => $result['transaction_id'],
            'status' => $result['status']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['error']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Payment processing error: ' . $e->getMessage()]);
}
?>
