<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/db_connect.php';

echo "<h2>COMPLETION REPORT DATABASE STATUS</h2>";
echo "<pre>";

try {
    // Check if completion_report_database table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'completion_report_database'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
        echo "✓ Table 'completion_report_database' EXISTS\n";
        
        // Check table structure
        $structureQuery = "DESCRIBE completion_report_database";
        $structureResult = $conn->query($structureQuery);
        echo "\nTable Structure:\n";
        while ($column = $structureResult->fetch_assoc()) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // Count records
        $countQuery = "SELECT COUNT(*) as total FROM completion_report_database";
        $countResult = $conn->query($countQuery);
        $countRow = $countResult->fetch_assoc();
        echo "\nCurrent Records: " . $countRow['total'] . "\n";
        
        if ($countRow['total'] == 0) {
            echo "\n❌ Table is EMPTY - Need to populate it\n";
            echo "\n=== ATTEMPTING TO POPULATE ===\n";
            
            // Get all students with their payment records (for or_num foreign key)
            $studentQuery = "SELECT DISTINCT s.student_id, s.student_firstname, s.student_lastname, s.student_middlename,
                            s.student_year, s.student_course, s.student_section, s.permanent_address, s.contact_num,
                            sc.unit_stats, sc.completion_remarks,
                            pr.or_num
                            FROM student_information_database s
                            LEFT JOIN student_clearance_database sc ON s.student_id = sc.student_id
                            LEFT JOIN payment_record_database pr ON s.student_id = pr.student_id
                            WHERE s.student_id IS NOT NULL";
            
            $studentResult = $conn->query($studentQuery);
            
            if ($studentResult && $studentResult->num_rows > 0) {
                $insertCount = 0;
                $skipCount = 0;
                while ($student = $studentResult->fetch_assoc()) {
                    $student_id = $student['student_id'];
                    $or_num = $student['or_num']; // Can be NULL

                    $studentrecord_id = 'SR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                    $student_firstname = $student['student_firstname'] ?? '';
                    $student_lastname = $student['student_lastname'] ?? '';
                    $student_middlename = $student['student_middlename'] ?? '';
                    $student_year = $student['student_year'] ?? '';
                    $student_course = $student['student_course'] ?? '';
                    $student_section = $student['student_section'] ?? '';
                    $student_address = $student['permanent_address'] ?? '';
                    $contact_num = $student['contact_num'] ?? '';
                    $unit_stats = $student['unit_stats'] ?? 'Pending';
                    $remarks = $student['completion_remarks'] ?? '';
                    
                    // Insert into completion_report_database
                    // If or_num is empty, insert NULL so records appear in completion report even without payment
                    $insertSQL = "INSERT INTO completion_report_database 
                        (studentrecord_id, or_num, student_id, student_firstname, student_lastname, student_middlename,
                         student_year, student_course, student_section, student_address, contact_num, unit_stats, remarks) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $insertStmt = $conn->prepare($insertSQL);
                    if (!$insertStmt) {
                        echo "Prepare error: " . $conn->error . "\n";
                        continue;
                    }

                    // Bind with nullable or_num: if empty, bind null variable
                    if (empty($or_num)) {
                        $nullOr = null;
                        $insertStmt->bind_param('sssssssssssss', $studentrecord_id, $nullOr, $student_id, $student_firstname, 
                                               $student_lastname, $student_middlename, $student_year, $student_course, 
                                               $student_section, $student_address, $contact_num, $unit_stats, $remarks);
                    } else {
                        $insertStmt->bind_param('sssssssssssss', $studentrecord_id, $or_num, $student_id, $student_firstname, 
                                               $student_lastname, $student_middlename, $student_year, $student_course, 
                                               $student_section, $student_address, $contact_num, $unit_stats, $remarks);
                    }

                    if ($insertStmt->execute()) {
                        $insertCount++;
                        if (empty($or_num)) $skipCount++; // count how many had no payment but were inserted with NULL
                    } else {
                        echo "Error inserting $student_id: " . $insertStmt->error . "\n";
                    }
                    $insertStmt->close();
                }

                echo "✓ Inserted $insertCount records into completion_report_database\n";
                if ($skipCount > 0) {
                    echo "⚠ $skipCount records had no payment number; inserted with NULL or_num so they appear in the report.\n";
                }
                
                // Verify
                $verifyQuery = "SELECT COUNT(*) as total FROM completion_report_database";
                $verifyResult = $conn->query($verifyQuery);
                $verifyRow = $verifyResult->fetch_assoc();
                echo "\n✓ Total records now in completion_report_database: " . $verifyRow['total'] . "\n";
                
                // Show sample
                echo "\nSample Records:\n";
                $sampleQuery = "SELECT student_id, student_firstname, student_lastname, unit_stats, remarks FROM completion_report_database LIMIT 5";
                $sampleResult = $conn->query($sampleQuery);
                while ($row = $sampleResult->fetch_assoc()) {
                    echo "  - " . $row['student_id'] . " | " . $row['student_firstname'] . " " . $row['student_lastname'] . " | " . $row['unit_stats'] . "\n";
                }
            } else {
                echo "No student records found to populate\n";
            }
        } else {
            echo "\n✓ Table has " . $countRow['total'] . " records\n";
            
            // Show sample records
            echo "\nSample Records:\n";
            $sampleQuery = "SELECT student_id, student_firstname, student_lastname, unit_stats, remarks 
                           FROM completion_report_database 
                           ORDER BY studentrecord_id DESC 
                           LIMIT 10";
            $sampleResult = $conn->query($sampleQuery);
            
            while ($row = $sampleResult->fetch_assoc()) {
                echo "  - " . $row['student_id'] . " | " . substr($row['student_firstname'] . ' ' . $row['student_lastname'], 0, 20) . " | " . $row['unit_stats'] . "\n";
            }
        }
    } else {
        echo "❌ Table 'completion_report_database' DOES NOT EXIST\n";
        echo "\n=== CREATING TABLE ===\n";
        
        $createTableSQL = "CREATE TABLE IF NOT EXISTS completion_report_database (
            report_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(50) NOT NULL UNIQUE,
            student_name VARCHAR(255),
            clearance_id VARCHAR(50),
            clearance_status VARCHAR(100),
            completion_remarks TEXT,
            date_completed DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_student_id (student_id),
            INDEX idx_clearance_status (clearance_status),
            INDEX idx_created_at (created_at)
        )";
        
        if ($conn->query($createTableSQL)) {
            echo "✓ Table 'completion_report_database' created successfully\n";
            
            // Now populate it
            echo "\n=== POPULATING TABLE ===\n";
            
            $studentQuery = "SELECT DISTINCT s.student_id, s.student_firstname, s.student_lastname, 
                            sc.clearance_id, sc.clearance_status, sc.completion_remarks
                            FROM student_information_database s
                            LEFT JOIN student_clearance_database sc ON s.student_id = sc.student_id";
            
            $studentResult = $conn->query($studentQuery);
            
            if ($studentResult && $studentResult->num_rows > 0) {
                $insertCount = 0;
                while ($student = $studentResult->fetch_assoc()) {
                    $student_id = $student['student_id'];
                    $student_name = $student['student_firstname'] . ' ' . $student['student_lastname'];
                    $clearance_id = $student['clearance_id'] ?? 'N/A';
                    $clearance_status = $student['clearance_status'] ?? 'Pending';
                    $completion_remarks = $student['completion_remarks'] ?? '';
                    
                    $insertSQL = "INSERT INTO completion_report_database 
                        (student_id, student_name, clearance_id, clearance_status, completion_remarks, date_completed, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                    
                    $insertStmt = $conn->prepare($insertSQL);
                    if (!$insertStmt) {
                        continue;
                    }
                    
                    $insertStmt->bind_param('sssss', $student_id, $student_name, $clearance_id, $clearance_status, $completion_remarks);
                    
                    if ($insertStmt->execute()) {
                        $insertCount++;
                    }
                    $insertStmt->close();
                }
                
                echo "✓ Populated $insertCount records into completion_report_database\n";
                
                // Verify
                $verifyQuery = "SELECT COUNT(*) as total FROM completion_report_database";
                $verifyResult = $conn->query($verifyQuery);
                $verifyRow = $verifyResult->fetch_assoc();
                echo "✓ Total records now in table: " . $verifyRow['total'] . "\n";
            }
        } else {
            echo "❌ Error creating table: " . $conn->error . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
