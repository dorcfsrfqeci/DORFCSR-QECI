<?php
// Backfill script: populate `or_num` in completion_report_database from payment_record_database
// Run from CLI: php backfill_completion_or.php

if (php_sapi_name() !== 'cli') {
    echo "This script is intended to be run from the command line.\n";
}

require_once __DIR__ . '/db_connect.php';

echo "Starting backfill of or_num in completion_report_database...\n";

$select = $conn->prepare("SELECT studentrecord_id, student_id FROM completion_report_database WHERE (or_num IS NULL OR or_num = '') AND student_id IS NOT NULL");
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

$findPayment = $conn->prepare("SELECT or_num FROM payment_record_database WHERE student_id = ? ORDER BY payment_date DESC LIMIT 1");
$update = $conn->prepare("UPDATE completion_report_database SET or_num = ? WHERE studentrecord_id = ?");

if (!$findPayment || !$update) {
    echo "Prepare failed (find/update): " . $conn->error . "\n";
    exit(1);
}

$updated = 0;
$skipped = 0;

while ($row = $res->fetch_assoc()) {
    $studentrecord_id = $row['studentrecord_id'];
    $student_id = $row['student_id'];
    $findPayment->bind_param('s', $student_id);
    $findPayment->execute();
    $pRes = $findPayment->get_result();
    if ($pRes && $pRes->num_rows > 0) {
        $pRow = $pRes->fetch_assoc();
        $or = $pRow['or_num'];
        $update->bind_param('ss', $or, $studentrecord_id);
        if ($update->execute()) {
            $updated++;
        }
    } else {
        $skipped++;
    }
}

$select->close();
$findPayment->close();
$update->close();

echo "Backfill complete. Updated: $updated, Skipped (no payment): $skipped\n";

$conn->close();
exit(0);
