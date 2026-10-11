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

$student_id = $_SESSION['user_id'];

include __DIR__ . '/../db_connect.php';

try {
    // Get clearance status for the student
    $stmt = $conn->prepare("SELECT * FROM student_clearance_database WHERE student_id = ?");
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $clearance = $result->fetch_assoc();
        echo json_encode(['success' => true, 'clearance' => $clearance]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No clearance record found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
