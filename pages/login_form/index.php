<?php
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'Admin') {
        header('Location: /DORCFSRF/pages/admin_dashboard/dashboard.php');
        exit;
    } else if ($role === 'Staff') {
        header('Location: /DORCFSRF/pages/staff_dashboard/staff_dasboard.php');
        exit;
    } else if ($role === 'Student') {
        header('Location: /DORCFSRF/pages/student_dashboard/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Form</title>
  <link rel="stylesheet" href="/DORCFSRF/css/style.css" />
</head>

<body>
  <div class="btn-container">
    <div class="logo-circle">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="QECI Logo" />
    </div>
    <h1>Student Clearance and Scholastic Record</h1>
    <p>
      Welcome to the Online Request System of Quezonian Educational College,
      Inc. a convenient platform designed to streamline the processing of
      student clearances and scholastic records with faster, accurate, and
      transparent transactions for students and alumni.
    </p>
    <a href="./student.php" class="btn student">Student</a>
    <a href="./admin.php" class="btn admin">Admin</a>
    <a href="./staff.php" class="btn staff">Staff</a>
  </div>
</body>

</html>