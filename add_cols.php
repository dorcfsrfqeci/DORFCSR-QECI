<?php
include 'db_connect.php';

$query = "ALTER TABLE student_clearance_database 
          ADD COLUMN student_lastname VARCHAR(255) NULL,
          ADD COLUMN student_firstname VARCHAR(255) NULL,
          ADD COLUMN student_middlename VARCHAR(255) NULL,
          ADD COLUMN student_year VARCHAR(50) NULL,
          ADD COLUMN student_course VARCHAR(255) NULL,
          ADD COLUMN student_section VARCHAR(50) NULL,
          ADD COLUMN school_year VARCHAR(50) NULL,
          ADD COLUMN student_address VARCHAR(255) NULL;";

if ($conn->query($query) === TRUE) {
    echo "Columns added successfully.\n";
} else {
    echo "Error adding columns: " . $conn->error . "\n";
}
?>
