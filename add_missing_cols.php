<?php
include 'db_connect.php';

$query = "ALTER TABLE student_clearance_database 
          ADD COLUMN studentrecord_id varchar(15) NULL,
          ADD COLUMN or_num varchar(20) NULL,
          ADD COLUMN contact_num varchar(30) NULL,
          ADD COLUMN release_date date NULL,
          ADD COLUMN remarks varchar(100) NULL,
          ADD COLUMN received_by varchar(50) NULL,
          ADD COLUMN scholasticrec_stat varchar(30) NULL;";

if ($conn->query($query) === TRUE) {
    echo "Columns added successfully.\n";
} else {
    echo "Error adding columns: " . $conn->error . "\n";
}
?>
