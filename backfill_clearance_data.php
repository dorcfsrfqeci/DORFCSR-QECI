<?php
include 'db_connect.php';

$query = "UPDATE student_clearance_database sc
          JOIN student_information_database si ON sc.student_id = si.student_id
          SET 
            sc.student_lastname = si.student_lastname,
            sc.student_firstname = si.student_firstname,
            sc.student_middlename = si.student_middlename,
            sc.student_year = si.student_year,
            sc.student_course = si.student_course,
            sc.student_section = si.student_section,
            sc.school_year = si.student_year,
            sc.student_address = si.permanent_address";

if ($conn->query($query) === TRUE) {
    echo "Existing records successfully updated with student information.\n";
} else {
    echo "Error updating records: " . $conn->error . "\n";
}
?>
