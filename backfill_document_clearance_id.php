<?php
// Backfill `clearance_id` in clearance_document_uploads using student_clearance_database
// Run: php backfill_document_clearance_id.php

if (php_sapi_name() !== 'cli') {
    echo "This script is intended to be run from the command line.\n";
}

require_once __DIR__ . '/db_connect.php';

echo "Starting backfill: clearance_document_uploads.clearance_id from student_clearance_database...\n";

$select = $conn->prepare("SELECT id, student_id FROM clearance_document_uploads WHERE (clearance_id IS NULL OR clearance_id = '')");
if (!$select) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit(1);
}

$select->execute();
$res = $select->get_result();
if (!$res) {
    echo "Query failed: " . $conn->error . "\n";
    exit(1);
}

$findClearance = $conn->prepare("SELECT clearance_id FROM student_clearance_database WHERE student_id = ? LIMIT 1");
$update = $conn->prepare("UPDATE clearance_document_uploads SET clearance_id = ? WHERE id = ?");

if (!$findClearance || !$update) {
    echo "Prepare failed (find/update): " . $conn->error . "\n";
    exit(1);
}

$updated = 0;
$skipped = 0;

while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $student_id = $row['student_id'];
    if (empty($student_id)) {
        $skipped++;
        continue;
    }

    $findClearance->bind_param('s', $student_id);
    $findClearance->execute();
    $cRes = $findClearance->get_result();
    if ($cRes && $cRes->num_rows > 0) {
        $cRow = $cRes->fetch_assoc();
        $clearance_id = $cRow['clearance_id'];
        $update->bind_param('si', $clearance_id, $id);
        if ($update->execute()) {
            $updated++;
        }
    } else {
        $skipped++;
    }
}

$select->close();
$findClearance->close();
$update->close();

echo "Backfill complete. Updated: $updated, Skipped: $skipped\n";

$conn->close();
exit(0);
