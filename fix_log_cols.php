<?php
include 'db_connect.php';

$query = "ALTER TABLE clearance_approvals_log 
          ADD COLUMN staff_id varchar(50) NULL,
          ADD COLUMN staff_name varchar(255) NULL,
          ADD COLUMN department_field varchar(100) NULL,
          ADD COLUMN status varchar(50) NULL;";

if ($conn->query($query) === TRUE) {
    echo "Columns added to log successfully.\n";
} else {
    echo "Error adding columns: " . $conn->error . "\n";
}
?>
