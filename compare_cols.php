<?php
include 'db_connect.php';

echo "=== clearance_approvals_log ===\n";
$res1 = $conn->query("DESCRIBE clearance_approvals_log");
$log_cols = [];
if($res1) {
    while($row = $res1->fetch_assoc()) {
        echo $row['Field'] . "\n";
        $log_cols[] = $row['Field'];
    }
}

echo "=== student_clearance_database ===\n";
$res2 = $conn->query("DESCRIBE student_clearance_database");
$sc_cols = [];
if($res2) {
    while($row = $res2->fetch_assoc()) {
        echo $row['Field'] . "\n";
        $sc_cols[] = $row['Field'];
    }
}

echo "=== missing columns in sc ===\n";
foreach($log_cols as $col) {
    if(!in_array($col, $sc_cols)) {
        echo $col . "\n";
    }
}
?>
