<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in and is staff or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Staff', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

include __DIR__ . '/../db_connect.php';

try {
    // Get all students with their current clearance status
    $query = "SELECT 
        s.student_id,
        CONCAT(s.student_firstname, ' ', s.student_lastname) as name,
        CONCAT(LOWER(REPLACE(CONCAT(s.student_firstname, s.student_lastname), ' ', '')), '@gmail.com') as email,
        COALESCE(sc.unit_stats, 'Not Set') as raw_status,
        s.updated_at as last_updated,
        CASE 
            WHEN LOWER(COALESCE(sc.unit_stats, 'pending')) IN ('cleared', 'approved') THEN 'cleared'
            WHEN LOWER(COALESCE(sc.unit_stats, 'pending')) = 'in progress' THEN 'in-progress'
            ELSE 'pending'
        END as status
    FROM student_information_database s
    LEFT JOIN student_clearance_database sc ON s.student_id = sc.student_id
    ORDER BY s.updated_at DESC, s.student_id ASC";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];

        // Check if profile image exists
        $profile_image = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.jpg';
        $profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.png';
        $profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.gif';

        $student_profile_image = null;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
            $student_profile_image = $profile_image;
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
            $student_profile_image = $profile_image_alt;
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
            $student_profile_image = $profile_image_alt2;
        }

        $students[] = [
            'student_id' => $row['student_id'],
            'name' => htmlspecialchars($row['name']),
            'email' => htmlspecialchars($row['email']),
            'status' => $row['status'],
            'raw_status' => $row['raw_status'],
            'profile_image' => $student_profile_image,
            'last_updated' => $row['last_updated']
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($students),
        'students' => $students,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>