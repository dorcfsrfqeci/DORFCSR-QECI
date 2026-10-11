<?php
// clear_session_by_email.php
// Usage: open in browser: http://localhost/DORCFSRF/clear_session_by_email.php?email=admin@qeci.edu.ph

ini_set('display_errors',1);
error_reporting(E_ALL);

include __DIR__ . '/db_connect.php';

$email = trim($_GET['email'] ?? 'admin@qeci.edu.ph');
$sessionsDir = __DIR__ . '/sessions';

if (!is_dir($sessionsDir)) {
    echo "Sessions directory not found: $sessionsDir";
    exit;
}

$foundUserId = null;
$foundUsername = null;
$stmt = $conn->prepare("SELECT user_id, username FROM user_database WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if ($row) {
        $foundUserId = $row['user_id'];
        $foundUsername = $row['username'];
    }
    $stmt->close();
}

$deleted = [];
$checked = 0;
$files = glob($sessionsDir . DIRECTORY_SEPARATOR . 'sess_*');
foreach ($files as $f) {
    $checked++;
    $content = @file_get_contents($f);
    if ($content === false) continue;
    $match = false;
    if ($foundUserId && strpos($content, $foundUserId) !== false) $match = true;
    if (!$match && $foundUsername && strpos($content, $foundUsername) !== false) $match = true;
    if (!$match && strpos($content, $email) !== false) $match = true;
    // also check common session keys
    if (!$match) {
        // look for serialized 'email' or similar
        if (strpos($content, 'email|') !== false && strpos($content, $email) !== false) $match = true;
    }
    if ($match) {
        if (@unlink($f)) {
            $deleted[] = basename($f);
        } else {
            $deleted[] = basename($f) . ' (failed to delete)';
        }
    }
}

header('Content-Type: text/plain');
echo "Clearing sessions for: $email\n";
if ($foundUserId) echo "Matched user_id: $foundUserId\n";
if ($foundUsername) echo "Matched username: $foundUsername\n";
echo "Scanned $checked session files. Deleted: " . count($deleted) . "\n";
if (!empty($deleted)) {
    echo "Deleted files:\n";
    foreach ($deleted as $d) echo " - $d\n";
} else {
    echo "No session files matched.\n";
}

echo "\nIf you need me to also expire sessions in Redis or another store, tell me where they are stored.\n";

?>