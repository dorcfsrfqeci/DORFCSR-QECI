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

include __DIR__ . '/../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$role = trim($data['role'] ?? '');
$user_id = trim($data['user_id'] ?? '');
$full_name = trim($data['full_name'] ?? '');
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';

$allowed_roles = ['Student', 'Staff'];

if ($role === '' || $full_name === '' || $username === '' || $email === '' || $password === '' || $confirm_password === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
} elseif (!in_array($role, $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
} elseif ($role === 'Staff' && !preg_match('/^STF\d{3}$/', $user_id)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID must be in format: STF001, STF002, etc.']);
    exit;
} elseif ($role === 'Student' && trim($user_id) === '') {
    echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
    exit;
} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username must be 3 to 30 characters and may contain letters, numbers, dot, underscore, or hyphen.']);
    exit;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
} elseif (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
} elseif ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
} else {
    $check = $conn->prepare('SELECT account_id FROM user_database WHERE user_id = ? OR username = ? OR email = ?');
    $check->bind_param('sss', $user_id, $username, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'User ID, username, or email already exists.']);
        $check->close();
        exit;
    }
    $check->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO user_database (user_id, full_name, role, email, username, password) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $user_id, $full_name, $role, $email, $username, $hashed_password);

    if ($stmt->execute()) {
        if ($role === 'Staff') {
            $unit_concern = trim($data['unit_concern'] ?? '');
            if (!empty($unit_concern)) {
                $unitStmt = $conn->prepare('INSERT INTO units_concerned (unit_name, staff_id) VALUES (?, ?)');
                $unitStmt->bind_param('ss', $unit_concern, $user_id);
                $unitStmt->execute();
                $unitStmt->close();
            }
        }
        echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Account creation failed: ' . $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>
