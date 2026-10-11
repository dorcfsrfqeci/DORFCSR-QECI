<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Restore/ensure completion_report_database schema and payment or_num column
// Usage: open in browser: http://localhost/DORCFSRF/restore_completion_report.php

echo "<h2>Restore Completion Report - Running</h2>";
echo "<pre>";

include __DIR__ . '/db_connect.php';

try {
    // Ensure payment_record_database exists
    $tblRes = $conn->query("SHOW TABLES LIKE 'payment_record_database'");
    if (!($tblRes && $tblRes->num_rows > 0)) {
        throw new Exception("Table 'payment_record_database' does not exist. Run create_tables.sql first or create the table.");
    }

    // 1) Add or_num column if missing
    $colRes = $conn->query("SHOW COLUMNS FROM payment_record_database LIKE 'or_num'");
    if (!($colRes && $colRes->num_rows > 0)) {
        $alter = "ALTER TABLE payment_record_database ADD COLUMN or_num VARCHAR(100) DEFAULT NULL";
        if ($conn->query($alter) === TRUE) {
            echo "✓ Added column 'or_num' to payment_record_database\n";
        } else {
            echo "⚠ Failed to add 'or_num': " . $conn->error . "\n";
        }
    } else {
        echo "✓ Column 'or_num' already exists\n";
    }

    // 2) Ensure index on or_num
    $schema = $conn->real_escape_string($conn->query('SELECT DATABASE()')->fetch_row()[0]);
    $idxQuery = "SELECT COUNT(*) as cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'payment_record_database' AND index_name = 'idx_payment_or_num'";
    $idxRes = $conn->query($idxQuery);
    $idxRow = $idxRes->fetch_assoc();
    if (intval($idxRow['cnt']) === 0) {
        $createIdx = "CREATE INDEX idx_payment_or_num ON payment_record_database (or_num)";
        if ($conn->query($createIdx) === TRUE) {
            echo "✓ Created index 'idx_payment_or_num' on payment_record_database(or_num)\n";
        } else {
            echo "⚠ Failed to create index: " . $conn->error . "\n";
        }
    } else {
        echo "✓ Index 'idx_payment_or_num' already exists\n";
    }

    // 3) Create completion_report_database if missing
    $tblRes2 = $conn->query("SHOW TABLES LIKE 'completion_report_database'");
    if (!($tblRes2 && $tblRes2->num_rows > 0)) {
        $create = "CREATE TABLE IF NOT EXISTS completion_report_database (
            studentrecord_id VARCHAR(64) PRIMARY KEY,
            or_num VARCHAR(100),
            student_id VARCHAR(50),
            student_lastname VARCHAR(100),
            student_firstname VARCHAR(100),
            student_middlename VARCHAR(100),
            student_year VARCHAR(10),
            student_course VARCHAR(100),
            student_section VARCHAR(50),
            student_address TEXT,
            contact_num VARCHAR(50),
            unit_con VARCHAR(50),
            unit_stats VARCHAR(50) DEFAULT 'Pending',
            release_date DATETIME DEFAULT NULL,
            remarks TEXT,
            received_by VARCHAR(255),
            scholasticrec_stat VARCHAR(50) DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_student_id (student_id),
            INDEX idx_or_num (or_num)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if ($conn->query($create) === TRUE) {
            echo "✓ Created table 'completion_report_database'\n";
        } else {
            echo "⚠ Failed to create table: " . $conn->error . "\n";
        }

        // Attempt to add FK constraint (requires referenced column to be indexed)
        // Check if a FK with this name exists
        $fkCheck = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'completion_report_database' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_completion_payment'");
        $fkCnt = $fkCheck->fetch_assoc()['cnt'] ?? 0;
        if (intval($fkCnt) === 0) {
            $addFk = "ALTER TABLE completion_report_database ADD CONSTRAINT fk_completion_payment FOREIGN KEY (or_num) REFERENCES payment_record_database(or_num) ON DELETE SET NULL ON UPDATE CASCADE";
            if ($conn->query($addFk) === TRUE) {
                echo "✓ Added foreign key fk_completion_payment -> payment_record_database(or_num)\n";
            } else {
                echo "⚠ Could not add FK (non-fatal): " . $conn->error . "\n";
            }
        } else {
            echo "✓ Foreign key 'fk_completion_payment' already exists\n";
        }
    } else {
        echo "✓ Table 'completion_report_database' already exists\n";
    }

    echo "\n---\n";
    echo "Ready to populate the table. Click the link below to run the population script.\n";
    $setupUrl = '/DORCFSRF/setup_completion_report.php';
    echo "\nOpen in browser: http://localhost" . $setupUrl . "\n";
    echo "\nOr click: <a href=\"$setupUrl\">Run setup_completion_report.php</a>\n";
        // Auto-run the setup script now so users get a populated completion_report_database in one step.
        echo "\n=== Auto-running population (setup_completion_report.php) ===\n";
        // Include will execute the setup script and output its results inline.
        include __DIR__ . '/setup_completion_report.php';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
