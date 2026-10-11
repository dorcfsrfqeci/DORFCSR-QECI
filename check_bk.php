<?php
include 'db_connect.php';
$res = $conn->query('DESCRIBE student_clearance_database_bk_20260507_000807');
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
?>
