<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    http_response_code(401);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    http_response_code(400);
    exit;
}

// Validate required fields
$required_fields = ['payment_method'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        http_response_code(400);
        exit;
    }
}

// Validate payment method value
$valid_methods = ['GCash', 'PayMaya', 'Pending'];
if (!in_array($data['payment_method'], $valid_methods)) {
    echo json_encode(['success' => false, 'message' => "Invalid payment method. Must be: " . implode(', ', $valid_methods)]);
    http_response_code(400);
    exit;
}

// Database connection
include __DIR__ . '/../db_connect.php';

try {
    $student_id = $_SESSION['user_id'];
    $payment_method = $data['payment_method'];
    $payment_date = date('Y-m-d');
    $or_num = $data['transaction_id'] ?? null;  // Use provided transaction_id if available

    // If no transaction_id provided, get the most recent payment record ID for this student
    if (!$or_num) {
        $getLatestStmt = $conn->prepare(
            "SELECT or_num FROM payment_record_database 
            WHERE student_id = ? 
            ORDER BY payment_date DESC, or_num DESC 
            LIMIT 1"
        );

        if (!$getLatestStmt) {
            throw new Exception("Get latest prepare failed: " . $conn->error);
        }

        $getLatestStmt->bind_param('s', $student_id);
        $getLatestStmt->execute();
        $result = $getLatestStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("No payment record found for this student");
        }
        
        $row = $result->fetch_assoc();
        $or_num = $row['or_num'];
        $getLatestStmt->close();
    }

    // Now update that specific payment record
    $stmt = $conn->prepare(
        "UPDATE payment_record_database 
        SET payment_method = ?, payment_date = ?
        WHERE or_num = ? AND student_id = ?"
    );

    if (!$stmt) {
        throw new Exception("Update prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ssss', $payment_method, $payment_date, $or_num, $student_id);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($affected === 0) {
        throw new Exception("No payment record found with OR number: $or_num");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment record updated successfully',
        'payment_method' => $payment_method,
        'or_num' => $or_num
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    http_response_code(500);
}
?>

