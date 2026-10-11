<?php
include __DIR__ . '/db_connect.php';

$allowed_columns = [
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

$result = $conn->query("SHOW COLUMNS FROM student_clearance_database");
$columns_to_drop = [];

while ($row = $result->fetch_assoc()) {
    $col_name = $row['Field'];
    if (!in_array($col_name, $allowed_columns)) {
        $columns_to_drop[] = $col_name;
    }
}

if (!empty($columns_to_drop)) {
    $drop_queries = [];
    foreach ($columns_to_drop as $col) {
        $drop_queries[] = "DROP COLUMN `$col`";
    }
    
    $alter_query = "ALTER TABLE student_clearance_database " . implode(", ", $drop_queries);
    echo "Executing: $alter_query\n";
    
    if ($conn->query($alter_query)) {
        echo "Successfully dropped columns: " . implode(", ", $columns_to_drop) . "\n";
    } else {
        echo "Error dropping columns: " . $conn->error . "\n";
    }
} else {
    echo "No columns to drop.\n";
}
?>
