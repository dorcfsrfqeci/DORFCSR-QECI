<?php
// force_logout_by_email.php
// Usage: open: http://localhost/DORCFSRF/force_logout_by_email.php?email=admin@qeci.edu.ph

ini_set('display_errors',1);
error_reporting(E_ALL);

include __DIR__ . '/db_connect.php';

$email = trim($_GET['email'] ?? '');
if (empty($email)) {
    echo "Usage: ?email=you@domain.com";
    exit;
}

$stmt = $conn->prepare("SELECT user_id, username FROM user_database WHERE email = ? LIMIT 1");
if (!$stmt) { echo "DB error: " . $conn->error; exit; }
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "No user found with email: $email";
    exit;
}

$user_id = $user['user_id'];
$username = $user['username'];

// Clear session_token in DB
$upd = $conn->prepare("UPDATE user_database SET session_token = NULL WHERE user_id = ?");
if ($upd) {
    $upd->bind_param('s', $user_id);
    if ($upd->execute()) {
        echo "Cleared session_token for user_id: $user_id\n";
    } else {
        echo "Failed to clear session_token: " . $upd->error . "\n";
    }
    $upd->close();
} else {
    echo "Prepare failed: " . $conn->error . "\n";
}

// Also attempt to delete session files matching user_id or username
$sessionsDir = __DIR__ . '/sessions';
$deleted = [];
if (is_dir($sessionsDir)) {
    $files = glob($sessionsDir . DIRECTORY_SEPARATOR . 'sess_*');
    foreach ($files as $f) {
        $content = @file_get_contents($f);
        if ($content === false) continue;
        if ((strpos($content, $user_id) !== false) || (strpos($content, $username) !== false) || (strpos($content, $email) !== false)) {
            if (@unlink($f)) $deleted[] = basename($f); else $deleted[] = basename($f) . ' (failed)';
        }
    }
}

echo "Deleted session files: " . count($deleted) . "\n";
foreach ($deleted as $d) echo " - $d\n";

echo "Done. The account should be logged out on other devices.\n";

?>