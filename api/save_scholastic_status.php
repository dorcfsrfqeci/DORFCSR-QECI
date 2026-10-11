<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in and is Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    http_response_code(401);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['student_id']) || !isset($data['status']) || !isset($data['last_updated'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    http_response_code(400);
    exit;
}

$student_id   = trim($data['student_id']);
$status       = trim($data['status']);
$last_updated = trim($data['last_updated']);
$system_remarks = isset($data['system_remarks']) ? trim($data['system_remarks']) : null;

// Validate status
$valid_statuses = ['Pending', 'In Progress', 'Ready for Release'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    http_response_code(400);
    exit;
}

// Validate date
if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $last_updated)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Expected YYYY-MM-DD']);
    http_response_code(400);
    exit;
}

include __DIR__ . '/../db_connect.php';

try {
    $stmt = $conn->prepare("UPDATE student_information_database SET scholastic_status = ?, scholastic_last_updated = ?, system_remarks = ? WHERE student_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssss", $status, $last_updated, $system_remarks, $student_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $stmt->close();

    // Also sync these to student_clearance_database as requested by user
    $sc_stmt = $conn->prepare("UPDATE student_clearance_database SET scholasticrec_stat = ?, release_date = ?, remarks = ? WHERE student_id = ?");
    if ($sc_stmt) {
        $sc_stmt->bind_param("ssss", $status, $last_updated, $system_remarks, $student_id);
        $sc_stmt->execute();
        $sc_stmt->close();
    }

    // Fetch student name for logging
    $student_stmt = $conn->prepare("SELECT student_firstname, student_lastname FROM student_information_database WHERE student_id = ?");
    $student_stmt->bind_param("s", $student_id);
    $student_stmt->execute();
    $student_res = $student_stmt->get_result();
    $student_name = "Student ($student_id)";
    if ($student_res && $row = $student_res->fetch_assoc()) {
        $student_name = trim($row['student_firstname'] . ' ' . $row['student_lastname']);
    }
    $student_stmt->close();

    include_once $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/log_audit.php';
    log_activity($conn, $student_id, $student_name, 'Student', 'Scholastic Record', "Scholastic Record Status updated to $status", 'Success', 'Scholastic', $student_id);

    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    http_response_code(500);
}
?>
