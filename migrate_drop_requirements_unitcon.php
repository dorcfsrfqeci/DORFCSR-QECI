<?php
// Migration: drop `requirements` and `unit_con` columns from student_clearance_database
// Run: php migrate_drop_requirements_unitcon.php

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
}

require_once __DIR__ . '/db_connect.php';

echo "Checking columns in student_clearance_database...\n";

$cols = [];
$res = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_clearance_database'");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $cols[] = $r['COLUMN_NAME'];
    }
} else {
    echo "Failed to read schema: " . $conn->error . "\n";
    exit(1);
}

$toDrop = ['requirements', 'unit_con'];

foreach ($toDrop as $col) {
    if (in_array($col, $cols)) {
        echo "Dropping column: $col\n";
        // No FK expected, but attempt to drop any FK that references this column first
        $fkRes = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_clearance_database' AND COLUMN_NAME = '$col' AND REFERENCED_TABLE_NAME IS NOT NULL");
        if ($fkRes && $fkRes->num_rows > 0) {
            while ($fk = $fkRes->fetch_assoc()) {
                $fkName = $fk['CONSTRAINT_NAME'];
                echo " Dropping foreign key constraint: $fkName\n";
                if (!$conn->query("ALTER TABLE student_clearance_database DROP FOREIGN KEY `$fkName`")) {
                    echo "  Failed to drop FK $fkName: " . $conn->error . "\n";
                    exit(1);
                }
            }
        }

        if (!$conn->query("ALTER TABLE student_clearance_database DROP COLUMN `$col`")) {
            echo "Failed to drop column $col: " . $conn->error . "\n";
            exit(1);
        }
        echo " Column $col dropped.\n";
    } else {
        echo "Column $col not present, skipping.\n";
    }
}

echo "Migration complete.\n";
$conn->close();
exit(0);
