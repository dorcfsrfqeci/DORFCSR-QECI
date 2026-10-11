<?php
include 'db_connect.php';

$res = $conn->query("SELECT * FROM clearance_approvals_log LIMIT 1");
if($res && $row = $res->fetch_assoc()) {
    print_r($row);
} else {
    echo "No data or error: " . $conn->error;
}
?>
