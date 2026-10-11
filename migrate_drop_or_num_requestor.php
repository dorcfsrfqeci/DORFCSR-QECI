<?php
// Migration: drop `or_num` column (and its FK) from requestor_database
// Run: php migrate_drop_or_num_requestor.php

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
}

require_once __DIR__ . '/db_connect.php';

echo "Checking for 'or_num' column in requestor_database...\n";

$colRes = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requestor_database' AND COLUMN_NAME = 'or_num'");
if (!$colRes) {
    echo "Failed to query schema: " . $conn->error . "\n";
    exit(1);
}

if ($colRes->num_rows === 0) {
    echo "Column 'or_num' does not exist in requestor_database. Nothing to do.\n";
    exit(0);
}

// Find foreign key constraint (if any)
$fkRes = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requestor_database' AND COLUMN_NAME = 'or_num' AND REFERENCED_TABLE_NAME IS NOT NULL");
if ($fkRes && $fkRes->num_rows > 0) {
    $row = $fkRes->fetch_assoc();
    $fkName = $row['CONSTRAINT_NAME'];
    echo "Dropping foreign key constraint: $fkName\n";
    if (!$conn->query("ALTER TABLE requestor_database DROP FOREIGN KEY `$fkName`")) {
        echo "Failed to drop foreign key $fkName: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "No foreign key constraint found for 'or_num'.\n";
}

// Drop the column
echo "Dropping column 'or_num'...\n";
if (!$conn->query("ALTER TABLE requestor_database DROP COLUMN or_num")) {
    echo "Failed to drop column or_num: " . $conn->error . "\n";
    exit(1);
}

echo "Successfully removed 'or_num' from requestor_database.\n";

$conn->close();
exit(0);
