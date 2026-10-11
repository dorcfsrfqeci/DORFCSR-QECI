<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

include __DIR__ . '/../db_connect.php';

try {
    // Get all students with their information
    $query = "SELECT 
        s.student_id,
        CONCAT(s.student_firstname, ' ', s.student_lastname) as name,
        s.student_year,
        s.student_course,
        s.student_section,
        s.student_sem,
        s.contact_num,
        s.permanent_address,
        COALESCE(sc.unit_stats, 'Not Started') as clearance_status,
        s.created_at
    FROM student_information_database s
    LEFT JOIN student_clearance_database sc ON s.student_id = sc.student_id
    ORDER BY s.created_at DESC";

    $result = $conn->query($query);

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(['success' => true, 'students' => $students]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
