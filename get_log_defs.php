<?php
include 'db_connect.php';

$res1 = $conn->query("DESCRIBE clearance_approvals_log");
while($row = $res1->fetch_assoc()) {
    $col = $row['Field'];
    if(in_array($col, ['studentrecord_id', 'or_num', 'contact_num', 'release_date', 'remarks', 'received_by', 'scholasticrec_stat'])) {
        echo $col . " " . $row['Type'] . " " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
}
?>
