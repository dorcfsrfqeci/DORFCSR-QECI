<?php
// clear_admin_sessions_cli.php
// Usage: php clear_admin_sessions_cli.php
$targetEmail = 'admin@qeci.edu.ph';
$targetUserId = 'ADMIN001';
$targetUsername = 'admin';
$sessionsDir = __DIR__ . '/../sessions';
if (!is_dir($sessionsDir)) {
    echo "Sessions directory not found: $sessionsDir\n";
    exit(1);
}
$files = glob($sessionsDir . DIRECTORY_SEPARATOR . 'sess_*');
$deleted = [];
foreach ($files as $f) {
    $content = @file_get_contents($f);
    if ($content === false) continue;
    $match = false;
    if (strpos($content, $targetUserId) !== false) $match = true;
    if (!$match && strpos($content, $targetEmail) !== false) $match = true;
    if (!$match && strpos($content, 's:'.strlen($targetUsername).':"'.$targetUsername.'"') !== false) $match = true;
    if (!$match && preg_match('/"?username"?\W*[:=] ?"?'.$targetUsername.'"?/i', $content)) $match = true;
    if ($match) {
        if (@unlink($f)) $deleted[] = basename($f);
        else $deleted[] = basename($f) . ' (failed)';
    }
}

echo "Scanned " . count($files) . " session files. Deleted: " . count($deleted) . "\n";
if (!empty($deleted)) {
    echo "Deleted files:\n";
    foreach ($deleted as $d) echo " - $d\n";
} else {
    echo "No session files matched for admin account.\n";
}
?>