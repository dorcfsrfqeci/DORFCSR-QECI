<?php
include __DIR__ . '/db_connect.php';

$statsQuery = "
SELECT 
    COUNT(sid.student_id) as total_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) IN ('cleared', 'approved') THEN 1 ELSE 0 END) as cleared_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) = 'in progress' THEN 1 ELSE 0 END) as in_progress_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) NOT IN ('cleared', 'approved', 'in progress') THEN 1 ELSE 0 END) as pending_students
FROM student_information_database sid
LEFT JOIN student_clearance_database scd ON sid.student_id = scd.student_id
";

$statsResult = $conn->query($statsQuery);
if ($statsResult && $row = $statsResult->fetch_assoc()) {
    echo "Total: " . $row['total_students'] . "\n";
    echo "Cleared: " . $row['cleared_students'] . "\n";
    echo "In Progress: " . $row['in_progress_students'] . "\n";
    echo "Pending: " . $row['pending_students'] . "\n";
} else {
    echo "Query failed: " . $conn->error . "\n";
}

$listQ = "SELECT 
        CASE 
            WHEN LOWER(COALESCE(sc.unit_stats, 'pending')) IN ('cleared', 'approved') THEN 'cleared'
            WHEN LOWER(COALESCE(sc.unit_stats, 'pending')) = 'in progress' THEN 'in-progress'
            ELSE 'pending'
        END as status, count(*) as cnt
    FROM student_information_database s
    LEFT JOIN student_clearance_database sc ON s.student_id = sc.student_id
    GROUP BY status";
$res2 = $conn->query($listQ);
while ($r = $res2->fetch_assoc()) {
    echo "List API logic status: " . $r['status'] . " -> " . $r['cnt'] . "\n";
}
