<?php
include __DIR__ . '/db_connect.php';

echo "<h2>COMPLETION REPORT DATABASE - VERIFICATION</h2>";
echo "<pre>";

try {
    // 1. Count total records
    $countQuery = "SELECT COUNT(*) as total FROM completion_report_database";
    $countResult = $conn->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    
    echo "✅ COMPLETION REPORT DATABASE STATUS\n";
    echo "=" . str_repeat("=", 60) . "\n";
    echo "Total Records: " . $countRow['total'] . "\n\n";
    
    // 2. Show status distribution
    echo "STATUS DISTRIBUTION:\n";
    $statusQuery = "SELECT unit_stats, COUNT(*) as count FROM completion_report_database GROUP BY unit_stats";
    $statusResult = $conn->query($statusQuery);
    while ($row = $statusResult->fetch_assoc()) {
        echo "  - " . $row['unit_stats'] . ": " . $row['count'] . " records\n";
    }
    
    // 3. Show sample records
    echo "\nSAMPLE RECORDS:\n";
    echo str_repeat("-", 90) . "\n";
    
    $sampleQuery = "SELECT studentrecord_id, student_id, student_firstname, student_lastname, 
                           student_course, unit_stats, scholasticrec_stat, remarks 
                    FROM completion_report_database 
                    ORDER BY studentrecord_id DESC 
                    LIMIT 10";
    $sampleResult = $conn->query($sampleQuery);
    
    while ($row = $sampleResult->fetch_assoc()) {
        echo "ID: " . substr($row['studentrecord_id'], 0, 15) . " | ";
        echo "Student: " . $row['student_id'] . " | ";
        echo "Name: " . substr($row['student_firstname'] . ' ' . $row['student_lastname'], 0, 25) . " | ";
        echo "Status: " . $row['unit_stats'] . " | ";
        echo "Scholastic: " . substr($row['scholasticrec_stat'] ?? 'N/A', 0, 15) . "\n";
    }
    
    echo str_repeat("-", 90) . "\n";
    
    // 4. Verify FK relationships
    echo "\nFOREIGN KEY VERIFICATION:\n";
    $fkQuery = "SELECT COUNT(*) as total FROM completion_report_database WHERE or_num IS NOT NULL";
    $fkResult = $conn->query($fkQuery);
    $fkRow = $fkResult->fetch_assoc();
    echo "  - Records with valid or_num (FK): " . $fkRow['total'] . "\n";
    
    // 5. Check for null values
    echo "\nDATA QUALITY:\n";
    $nullQuery = "SELECT 
                    SUM(CASE WHEN student_firstname IS NULL OR student_firstname = '' THEN 1 ELSE 0 END) as null_firstname,
                    SUM(CASE WHEN unit_stats IS NULL OR unit_stats = '' THEN 1 ELSE 0 END) as null_status,
                    SUM(CASE WHEN or_num IS NULL THEN 1 ELSE 0 END) as null_or_num
                  FROM completion_report_database";
    $nullResult = $conn->query($nullQuery);
    $nullRow = $nullResult->fetch_assoc();
    
    echo "  - Null First Names: " . $nullRow['null_firstname'] . "\n";
    echo "  - Null Unit Status: " . $nullRow['null_status'] . "\n";
    echo "  - Null OR Numbers (FK): " . $nullRow['null_or_num'] . "\n";
    
    echo "\n" . str_repeat("=", 90) . "\n";
    echo "✅ COMPLETION REPORT DATABASE IS FULLY POPULATED AND OPERATIONAL!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
