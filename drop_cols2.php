<?php
include 'db_connect.php';

$query = "ALTER TABLE student_clearance_database 
          DROP COLUMN student_lastname,
          DROP COLUMN student_firstname,
          DROP COLUMN student_middlename,
          DROP COLUMN student_year,
          DROP COLUMN student_course,
          DROP COLUMN student_section,
          DROP COLUMN school_year,
          DROP COLUMN student_address;";

if ($conn->query($query) === TRUE) {
    echo "Columns dropped successfully.\n";
} else {
    echo "Error dropping columns: " . $conn->error . "\n";
}
?>
