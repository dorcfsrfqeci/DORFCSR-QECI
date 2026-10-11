<?php
// populate_completion_from_clearance.php
// Usage: http://localhost/DORCFSRF/populate_completion_from_clearance.php
ini_set('display_errors',1);
error_reporting(E_ALL);

include __DIR__ . '/db_connect.php';

echo "<h2>Populate completion_report_database from clearance data</h2><pre>";
try {
    // Diagnostic: current database and table info
    $curDbR = $conn->query("SELECT DATABASE() as dbname");
    $curDb = ($curDbR && $curDbR->num_rows>0) ? $curDbR->fetch_assoc()['dbname'] : '(unknown)';
    echo "Active database: $curDb\n";
    $tblChk0 = $conn->query("SHOW TABLES LIKE 'completion_report_database'");
    echo "completion_report_database exists: " . (($tblChk0 && $tblChk0->num_rows>0) ? 'YES' : 'NO') . "\n";
    $countBefore = $conn->query("SELECT COUNT(*) as cnt FROM completion_report_database");
    if ($countBefore && $countBefore->num_rows>0) echo "Rows before: " . $countBefore->fetch_assoc()['cnt'] . "\n";

    // Ensure autocommit is enabled
    $conn->autocommit(true);

    // Create a safe import backup table to capture rows that fail to insert into the main table
    $importTableSql = "CREATE TABLE IF NOT EXISTS completion_report_import (
        import_id INT AUTO_INCREMENT PRIMARY KEY,
        studentrecord_id VARCHAR(64),
        or_num VARCHAR(64),
        student_id VARCHAR(64),
        student_lastname VARCHAR(255),
        student_firstname VARCHAR(255),
        student_middlename VARCHAR(255),
        student_year VARCHAR(32),
        student_course VARCHAR(128),
        student_section VARCHAR(64),
        student_address TEXT,
        contact_num VARCHAR(64),
        unit_con VARCHAR(64),
        unit_stats VARCHAR(64),
        release_date DATETIME NULL,
        remarks TEXT,
        received_by VARCHAR(255),
        scholasticrec_stat VARCHAR(64),
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        original_error TEXT
    ) ENGINE=InnoDB";
    $conn->query($importTableSql);

    // Open logfile
    $logfile = __DIR__ . '/populate_completion_log.txt';
    file_put_contents($logfile, "\n--- Run at " . date('c') . " ---\n", FILE_APPEND);
    // Ensure completion_report_database exists
    $tblChk = $conn->query("SHOW TABLES LIKE 'completion_report_database'");
    if (!($tblChk && $tblChk->num_rows > 0)) {
        echo "Table completion_report_database does not exist. Run restore_completion_report.php first.\n";
        exit;
    }

        // Gather all students (include everyone so completion_report_database is populated)
        $sql = "SELECT s.student_id, s.student_firstname, s.student_lastname, s.student_middlename, s.student_year, s.student_course, s.student_section, s.permanent_address, s.contact_num,
            COALESCE(sc.unit_stats, 'Pending') as unit_stats, COALESCE(sc.completion_remarks, '') as completion_remarks, sc.clearance_id
            FROM student_information_database s
            LEFT JOIN student_clearance_database sc ON s.student_id = sc.student_id";

    $res = $conn->query($sql);
    if (!($res && $res->num_rows > 0)) {
        echo "No student records found to import.\n";
        exit;
    }

    $insertCount = 0; $updateCount = 0; $errors = 0;

    $selectStmt = $conn->prepare('SELECT studentrecord_id FROM completion_report_database WHERE student_id = ? LIMIT 1');
    $insertStmt = $conn->prepare('INSERT INTO completion_report_database (studentrecord_id, or_num, student_id, student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, student_address, contact_num, unit_stats, remarks, scholasticrec_stat, created_at, updated_at) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $updateStmt = $conn->prepare('UPDATE completion_report_database SET student_lastname = ?, student_firstname = ?, student_middlename = ?, student_year = ?, student_course = ?, student_section = ?, student_address = ?, contact_num = ?, unit_stats = ?, remarks = ?, scholasticrec_stat = ?, updated_at = NOW() WHERE student_id = ?');

    if (!$selectStmt || !$insertStmt || !$updateStmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    while ($row = $res->fetch_assoc()) {
        $student_id = $row['student_id'];
        $firstname = $row['student_firstname'] ?? '';
        $lastname = $row['student_lastname'] ?? '';
        $middlename = $row['student_middlename'] ?? '';
        $year = $row['student_year'] ?? '';
        $course = $row['student_course'] ?? '';
        $section = $row['student_section'] ?? '';
        $address = $row['permanent_address'] ?? '';
        $contact = $row['contact_num'] ?? '';
        $unit_stats = $row['unit_stats'] ?? 'Pending';
        $remarks = $row['completion_remarks'] ?? '';
        $scholastic = $row['clearance_id'] ?? null;

        // Check if exists
        $selectStmt->bind_param('s', $student_id);
        $selectStmt->execute();
        $selRes = $selectStmt->get_result();
        if ($selRes && $selRes->num_rows > 0) {
            // update main table
            $updateStmt->bind_param('sssssssssss', $lastname, $firstname, $middlename, $year, $course, $section, $address, $contact, $unit_stats, $remarks, $scholastic, $student_id);
            if ($updateStmt->execute()) {
                $updateCount++;
            } else {
                $errors++;
                $err = $updateStmt->error;
                echo "Update failed for $student_id: $err\n";
                file_put_contents($logfile, "UPDATE_ERROR $student_id: $err\n", FILE_APPEND);
                // fallback: insert/update into import table
                $ival = $conn->real_escape_string($studentrecord_id ?? '');
                $q = "INSERT INTO completion_report_import (studentrecord_id, student_id, student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, student_address, contact_num, unit_stats, remarks, scholasticrec_stat, created_at, updated_at, original_error) VALUES ('" . $conn->real_escape_string($studentrecord_id ?? '') . "', '" . $conn->real_escape_string($student_id) . "', '" . $conn->real_escape_string($lastname) . "', '" . $conn->real_escape_string($firstname) . "', '" . $conn->real_escape_string($middlename) . "', '" . $conn->real_escape_string($year) . "', '" . $conn->real_escape_string($course) . "', '" . $conn->real_escape_string($section) . "', '" . $conn->real_escape_string($address) . "', '" . $conn->real_escape_string($contact) . "', '" . $conn->real_escape_string($unit_stats) . "', '" . $conn->real_escape_string($remarks) . "', '" . $conn->real_escape_string($scholastic) . "', NOW(), NOW(), '" . $conn->real_escape_string($err) . "')";
                $conn->query($q);
            }
        } else {
            // insert into main table, fallback to import on failure
            $studentrecord_id = 'SR-' . strtoupper(substr(md5(uniqid($student_id, true)), 0, 8));
            $insertStmt->bind_param('sssssssssssss', $studentrecord_id, $student_id, $lastname, $firstname, $middlename, $year, $course, $section, $address, $contact, $unit_stats, $remarks, $scholastic);
            if ($insertStmt->execute()) {
                $insertCount++;
            } else {
                $errors++;
                $err = $insertStmt->error;
                echo "Insert failed for $student_id: $err\n";
                file_put_contents($logfile, "INSERT_ERROR $student_id: $err\n", FILE_APPEND);
                $q = "INSERT INTO completion_report_import (studentrecord_id, student_id, student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, student_address, contact_num, unit_stats, remarks, scholasticrec_stat, created_at, updated_at, original_error) VALUES ('" . $conn->real_escape_string($studentrecord_id) . "', '" . $conn->real_escape_string($student_id) . "', '" . $conn->real_escape_string($lastname) . "', '" . $conn->real_escape_string($firstname) . "', '" . $conn->real_escape_string($middlename) . "', '" . $conn->real_escape_string($year) . "', '" . $conn->real_escape_string($course) . "', '" . $conn->real_escape_string($section) . "', '" . $conn->real_escape_string($address) . "', '" . $conn->real_escape_string($contact) . "', '" . $conn->real_escape_string($unit_stats) . "', '" . $conn->real_escape_string($remarks) . "', '" . $conn->real_escape_string($scholastic) . "', NOW(), NOW(), '" . $conn->real_escape_string($err) . "')";
                $conn->query($q);
            }
        }
    }

    echo "Inserted: $insertCount\nUpdated: $updateCount\nErrors: $errors\n";
    // Diagnostics after operations
    $countAfter = $conn->query("SELECT COUNT(*) as cnt FROM completion_report_database");
    if ($countAfter && $countAfter->num_rows>0) echo "Rows after: " . $countAfter->fetch_assoc()['cnt'] . "\n";
    echo "DB errno: " . $conn->errno . ", error: " . $conn->error . "\n";
    echo "Done. Please refresh phpMyAdmin to see the rows.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>