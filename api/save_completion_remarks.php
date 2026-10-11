<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is staff or admin
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || ($role !== 'Staff' && $role !== 'Admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only staff or admin can modify completion remarks.']);
    exit;
}

include __DIR__ . '/../db_connect.php';

$student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
$completion_remarks = isset($_POST['completion_remarks']) ? trim($_POST['completion_remarks']) : null;

if (empty($student_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    // Check if record exists
    $checkExistsSql = "SELECT clearance_id FROM student_clearance_database WHERE student_id = ? LIMIT 1";
    $checkExistsStmt = $conn->prepare($checkExistsSql);
    $checkExistsStmt->bind_param('s', $student_id);
    $checkExistsStmt->execute();
    $existsResult = $checkExistsStmt->get_result();
    $recordExists = $existsResult->num_rows > 0;
    $checkExistsStmt->close();

    if ($recordExists) {
        $updateSql = "UPDATE completion_report_database 
                      SET remarks = ? 
                      WHERE student_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('ss', $completion_remarks, $student_id);
        if (!$updateStmt->execute()) {
            throw new Exception("Update execute failed: " . $updateStmt->error);
        }
        $updateStmt->close();
    } else {
        $clearance_id = 'CLR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 7));
        // Ensure completion report record exists
        $insertSql = "INSERT INTO completion_report_database (studentrecord_id, student_id, unit_stats, remarks) 
                      VALUES (?, ?, 'Pending', ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('sss', $clearance_id, $student_id, $completion_remarks);
        if (!$insertStmt->execute()) {
            throw new Exception("Insert execute failed: " . $insertStmt->error);
        }
        $insertStmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => "Completion remarks saved successfully"
    ]);

} catch (Exception $e) {
    error_log("Save completion remarks error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
