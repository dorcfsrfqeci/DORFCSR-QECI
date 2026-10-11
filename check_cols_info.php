<?php
include 'db_connect.php';
$res = $conn->query("DESCRIBE student_information_database");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . "\n";
}
