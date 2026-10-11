<?php
include 'db_connect.php';

$res = $conn->query("DESCRIBE clearance_approvals_log");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
