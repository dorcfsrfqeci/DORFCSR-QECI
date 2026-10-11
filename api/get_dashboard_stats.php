<?php
// Returns JSON with total, cleared, in_progress, pending counts based on unit_stats
header('Content-Type: application/json');
include __DIR__ . '/../db_connect.php';

$statsQuery = "
SELECT 
    COUNT(sid.student_id) as total_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) IN ('cleared', 'approved') THEN 1 ELSE 0 END) as cleared_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) = 'in progress' THEN 1 ELSE 0 END) as in_progress_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) NOT IN ('cleared', 'approved', 'in progress') THEN 1 ELSE 0 END) as pending_students
FROM student_information_database sid
LEFT JOIN student_clearance_database scd ON sid.student_id = scd.student_id
";

$result = $conn->query($statsQuery);
if ($result && $row = $result->fetch_assoc()) {
    echo json_encode([
        'total' => intval($row['total_students']),
        'cleared' => intval($row['cleared_students'] ?? 0),
        'in_progress' => intval($row['in_progress_students'] ?? 0),
        'pending' => intval($row['pending_students'] ?? 0)
    ]);
} else {
    echo json_encode(['total' => 0, 'cleared' => 0, 'in_progress' => 0, 'pending' => 0]);
}

?>