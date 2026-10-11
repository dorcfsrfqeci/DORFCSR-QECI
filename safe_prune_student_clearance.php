<?php
// safe_prune_student_clearance.php
// Preview and (with ?confirm=1) drop all columns from student_clearance_database
// except the allowed fields provided by the user.
// USAGE:
// 1) Preview: http://localhost/DORCFSRF/safe_prune_student_clearance.php
// 2) Execute: http://localhost/DORCFSRF/safe_prune_student_clearance.php?confirm=1

ini_set('display_errors',1);
error_reporting(E_ALL);
include __DIR__ . '/db_connect.php';

$allowed = [
    'clearance_id',
    'student_id',
    'student_lastname',
    'student_firstname',
    'student_middlename',
    'student_year',
    'student_course',
    'student_section',
    'school_year',
    'student_address',
    'requirements',
    'unit_con',
    'unit_stats'
];

$logFile = __DIR__ . '/safe_prune_student_clearance.txt';
file_put_contents($logFile, "\n--- RUN: " . date('c') . " ---\n", FILE_APPEND);

echo "<h2>Safe prune: student_clearance_database</h2><pre>";
file_put_contents($logFile, "Allowed columns: " . implode(', ', $allowed) . "\n", FILE_APPEND);

// Check table exists
$check = $conn->query("SHOW TABLES LIKE 'student_clearance_database'");
if (!($check && $check->num_rows>0)) {
    echo "Table student_clearance_database does not exist. Aborting.\n";
    file_put_contents($logFile, "Table missing\n", FILE_APPEND);
    echo "</pre>"; exit;
}

// Get current columns
$colsRes = $conn->query("SHOW COLUMNS FROM student_clearance_database");
$current = [];
while ($c = $colsRes->fetch_assoc()) $current[] = $c['Field'];

echo "Current columns:\n" . implode(', ', $current) . "\n\n";
file_put_contents($logFile, "Current columns: " . implode(', ', $current) . "\n", FILE_APPEND);

// Determine columns to drop (current minus allowed)
$toDrop = array_values(array_diff($current, $allowed));

if (empty($toDrop)) {
    echo "No columns to drop. Table already matches allowed set.\n";
    file_put_contents($logFile, "No columns to drop\n", FILE_APPEND);
    echo "</pre>"; exit;
}

echo "Columns that WOULD be dropped:\n" . implode(', ', $toDrop) . "\n\n";
file_put_contents($logFile, "To drop: " . implode(', ', $toDrop) . "\n", FILE_APPEND);

// Safety: create a backup table (full copy) before making destructive changes
$backup = 'student_clearance_database_backup_' . date('Ymd_His');
$bkSql = "CREATE TABLE `" . $conn->real_escape_string($backup) . "` AS SELECT * FROM student_clearance_database";
if ($conn->query($bkSql) === TRUE) {
    echo "Backup created: $backup\n";
    file_put_contents($logFile, "Backup created: $backup\n", FILE_APPEND);
} else {
    echo "Failed to create backup table: " . $conn->error . "\n";
    file_put_contents($logFile, "Backup failed: " . $conn->error . "\n", FILE_APPEND);
    echo "Aborting to avoid data loss.\n";
    echo "</pre>"; exit;
}

// If confirm parameter is not set, stop here (preview only)
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === '1';
if (!$confirm) {
    echo "Preview only. To execute the prune, re-run with ?confirm=1.\n";
    echo "Log: $logFile\n";
    echo "</pre>"; exit;
}

// Build ALTER TABLE DROP statements in one statement if possible
$dropParts = [];
foreach ($toDrop as $col) {
    // Skip primary key if somehow not in allowed (should not happen since clearance_id is allowed)
    if (strtolower($col) === 'clearance_id') continue;
    $dropParts[] = 'DROP COLUMN `' . $conn->real_escape_string($col) . '`';
}

if (empty($dropParts)) {
    echo "No safe drops to perform.\n";
    echo "</pre>"; exit;
}

$alterSql = 'ALTER TABLE student_clearance_database ' . implode(', ', $dropParts);

if ($conn->query($alterSql) === TRUE) {
    echo "Successfully dropped columns: " . implode(', ', $toDrop) . "\n";
    file_put_contents($logFile, "Dropped: " . implode(', ', $toDrop) . "\n", FILE_APPEND);
} else {
    echo "ALTER TABLE failed: " . $conn->error . "\n";
    file_put_contents($logFile, "ALTER_FAILED: " . $conn->error . "\n", FILE_APPEND);
    echo "You can restore from backup table: $backup\n";
    echo "</pre>"; exit;
}

// Show resulting columns
$resAfter = $conn->query("SHOW COLUMNS FROM student_clearance_database");
$after = [];
while ($c = $resAfter->fetch_assoc()) $after[] = $c['Field'];

echo "New columns:\n" . implode(', ', $after) . "\n";
file_put_contents($logFile, "New columns: " . implode(', ', $after) . "\n", FILE_APPEND);

echo "Completed. Backup kept as $backup. Log: $logFile\n";
file_put_contents($logFile, "Completed. Backup: $backup\n", FILE_APPEND);

echo "</pre>";
?>