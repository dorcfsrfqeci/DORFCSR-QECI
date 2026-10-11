<?php
// Backfill script: populate requestor_database from student_information_database
// Run from CLI: php backfill_requestor_database.php

if (php_sapi_name() !== 'cli') {
    echo "This script is intended to be run from the command line.\n";
}

require_once __DIR__ . '/db_connect.php';

echo "Starting backfill of requestor_database from student_information_database...\n";

// Temporarily disable foreign key checks to allow inserting backfilled rows
$conn->query("SET FOREIGN_KEY_CHECKS=0");

$select = $conn->prepare("SELECT student_id, student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, contact_num, permanent_address, scholastic_document, reason_clearance FROM student_information_database WHERE student_id IS NOT NULL");
if (!$select) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit(1);
}

$select->execute();
$result = $select->get_result();
if (!$result) {
    echo "Query failed: " . $conn->error . "\n";
    exit(1);
}

$colsRes = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requestor_database'");
$cols = [];
if ($colsRes) {
    while ($c = $colsRes->fetch_assoc()) {
        $cols[] = $c['COLUMN_NAME'];
    }
}

$using_new_schema = in_array('student_year', $cols);
// Determine primary key or identifying column for existence checks
$pk = null;
if (in_array('requestor_id', $cols)) {
    $pk = 'requestor_id';
} elseif (in_array('request_num', $cols)) {
    $pk = 'request_num';
}

if ($using_new_schema) {
    $insertStmt = $conn->prepare("INSERT INTO requestor_database (student_id, student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, contact_num, permanent_address, request_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'FormSubmission', 'Pending')");
    $updateStmt = $conn->prepare("UPDATE requestor_database SET student_lastname = ?, student_firstname = ?, student_middlename = ?, student_year = ?, student_course = ?, student_section = ?, contact_num = ?, permanent_address = ?, request_type = 'FormSubmission', status = 'Pending', updated_at = CURRENT_TIMESTAMP WHERE student_id = ?");
} else {
    $insertStmt = $conn->prepare("INSERT INTO requestor_database (request_num, student_id, or_num, student_lastname, student_firstname, student_middlename, student_course, last_attended, type_schdocs, date_request, purpose, current_stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'Pending')");
    $updateStmt = $conn->prepare("UPDATE requestor_database SET student_lastname = ?, student_firstname = ?, student_middlename = ?, student_course = ?, last_attended = ?, type_schdocs = ?, purpose = ?, current_stage = 'Pending', date_request = CURDATE() WHERE student_id = ?");
}

if (!$insertStmt || !$updateStmt) {
    echo "Prepare failed (insert/update): " . $conn->error . "\n";
    exit(1);
}

$inserted = 0;
$updated = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
    // Basic check: student_id must be present
    if (empty($student_id)) {
        $skipped++;
        continue;
    }

    if ($pk) {
        $check = $conn->prepare("SELECT $pk FROM requestor_database WHERE student_id = ? LIMIT 1");
    } else {
        $check = $conn->prepare("SELECT student_id FROM requestor_database WHERE student_id = ? LIMIT 1");
    }
    if (!$check) {
        echo "Prepare check failed: " . $conn->error . "\n";
        exit(1);
    }
    $check->bind_param('s', $student_id);
    $check->execute();
    $checkRes = $check->get_result();

    if ($checkRes && $checkRes->num_rows > 0) {
        // Update existing
            if ($using_new_schema) {
                $updateStmt->bind_param(
                    'sssssssss',
                    $row['student_lastname'],
                    $row['student_firstname'],
                    $row['student_middlename'],
                    $row['student_year'],
                    $row['student_course'],
                    $row['student_section'],
                    $row['contact_num'],
                    $row['permanent_address'],
                    $student_id
                );
            } else {
                // legacy schema update: include scholastic_document and reason_clearance into type_schdocs/purpose
                $last_attended = $row['student_year'] ?? '';
                $type_schdocs_val = $row['scholastic_document'] ?? 'FormSubmission';
                $purpose_val = $row['reason_clearance'] ?? 'Form Submission';
                $updateStmt->bind_param(
                    'ssssssss',
                    $row['student_lastname'],
                    $row['student_firstname'],
                    $row['student_middlename'],
                    $row['student_course'],
                    $last_attended,
                    $type_schdocs_val,
                    $purpose_val,
                    $student_id
                );
            }
        if ($updateStmt->execute()) {
            $updated++;
        }
        $check->close();
    } else {
        // Insert new
            if ($using_new_schema) {
                $insertStmt->bind_param(
                    'sssssssss',
                    $student_id,
                    $row['student_lastname'],
                    $row['student_firstname'],
                    $row['student_middlename'],
                    $row['student_year'],
                    $row['student_course'],
                    $row['student_section'],
                    $row['contact_num'],
                    $row['permanent_address']
                );
            } else {
                // legacy schema insert: generate request_num and populate type_schdocs/purpose
                $request_num = 'REQ-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
                $last_attended = $row['student_year'] ?? '';
                $or_num_val = '';
                $type_schdocs_val = $row['scholastic_document'] ?? 'FormSubmission';
                $purpose_val = $row['reason_clearance'] ?? 'Form Submission';
                $insertStmt->bind_param(
                    'ssssssssss',
                    $request_num,
                    $student_id,
                    $or_num_val,
                    $row['student_lastname'],
                    $row['student_firstname'],
                    $row['student_middlename'],
                    $row['student_course'],
                    $last_attended,
                    $type_schdocs_val,
                    $purpose_val
                );
            }
        if ($insertStmt->execute()) {
            $inserted++;
        }
        $check->close();
    }
}

$insertStmt->close();
$updateStmt->close();
$select->close();

echo "Backfill complete. Inserted: $inserted, Updated: $updated, Skipped: $skipped\n";

// Optionally, close connection
$conn->query("SET FOREIGN_KEY_CHECKS=1");
$conn->close();

exit(0);
