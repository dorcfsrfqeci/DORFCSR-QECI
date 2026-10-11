<?php
// clear_admin_sessions_force.php
// Usage examples:
// - Clear single user by email: http://localhost/DORCFSRF/clear_admin_sessions_force.php?email=admin@qeci.edu.ph
// - Clear single user by user_id: http://localhost/DORCFSRF/clear_admin_sessions_force.php?user_id=ADMIN001
// - Clear all Admins (DANGEROUS, requires confirm=1): http://localhost/DORCFSRF/clear_admin_sessions_force.php?role=Admin&confirm=1

ini_set('display_errors',1);
error_reporting(E_ALL);

include __DIR__ . '/db_connect.php';

$email = trim($_GET['email'] ?? '');
$user_id = trim($_GET['user_id'] ?? '');
$username = trim($_GET['username'] ?? '');
$role = trim($_GET['role'] ?? '');
$confirm = trim($_GET['confirm'] ?? '0');

header('Content-Type: text/plain');

if (empty($email) && empty($user_id) && empty($username) && empty($role)) {
    echo "Provide one of: ?email=, ?user_id=, ?username=, or ?role=Admin&confirm=1\n";
    exit;
}

$targets = [];

if (!empty($email) || !empty($user_id) || !empty($username)) {
    $query = "SELECT user_id, username, email, role FROM user_database WHERE 1=1";
    $params = [];
    $types = '';
    $where = [];
    if (!empty($email)) { $where[] = 'email = ?'; $params[] = $email; $types .= 's'; }
    if (!empty($user_id)) { $where[] = 'user_id = ?'; $params[] = $user_id; $types .= 's'; }
    if (!empty($username)) { $where[] = 'username = ?'; $params[] = $username; $types .= 's'; }
    if (!empty($where)) $query .= ' AND ' . implode(' AND ', $where);
    $stmt = $conn->prepare($query);
    if (!$stmt) { echo "DB prepare failed: " . $conn->error . "\n"; exit; }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $targets[] = $r;
    }
    $stmt->close();
}

if (!empty($role)) {
    if ($role !== 'Admin') {
        echo "Role clearing currently only allowed for 'Admin'.\n";
        exit;
    }
    if ($confirm !== '1') {
        echo "To clear all Admin session_tokens you must add &confirm=1 to the URL. Aborting.\n";
        exit;
    }
    $stmt = $conn->prepare("SELECT user_id, username, email, role FROM user_database WHERE role = ?");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $targets[] = $r;
    $stmt->close();
}

if (empty($targets)) {
    echo "No matching users found.\n";
    exit;
}

$cleared_db = [];
$deleted_files = [];

foreach ($targets as $t) {
    $uid = $t['user_id'];
    $uname = $t['username'];
    $uemail = $t['email'];

    // Clear DB token
    $upd = $conn->prepare("UPDATE user_database SET session_token = NULL WHERE user_id = ?");
    if ($upd) {
        $upd->bind_param('s', $uid);
        if ($upd->execute()) $cleared_db[] = $uid;
        $upd->close();
    }

    // Remove any session files that contain id/username/email
    $sessionsDir = __DIR__ . '/sessions';
    if (is_dir($sessionsDir)) {
        $files = glob($sessionsDir . DIRECTORY_SEPARATOR . 'sess_*');
        foreach ($files as $f) {
            $content = @file_get_contents($f);
            if ($content === false) continue;
            if ((strpos($content, $uid) !== false) || (strpos($content, $uname) !== false) || (strpos($content, $uemail) !== false)) {
                if (@unlink($f)) $deleted_files[] = basename($f); else $deleted_files[] = basename($f) . ' (failed)';
            }
        }
    }
}

echo "Targets processed: " . count($targets) . "\n";
if (!empty($cleared_db)) {
    echo "Cleared DB session_token for: \n";
    foreach ($cleared_db as $c) echo " - $c\n";
}
if (!empty($deleted_files)) {
    echo "Deleted session files: \n";
    foreach ($deleted_files as $d) echo " - $d\n";
}

echo "Done. Try logging in again.\n";

?>