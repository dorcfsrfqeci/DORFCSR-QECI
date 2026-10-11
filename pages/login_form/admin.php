<?php
session_start();
include __DIR__ . '/../../db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'Admin') {
    header('Location: /DORCFSRF/pages/admin_dashboard/dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = 'Please fill out all fields.';
  } else {
    $stmt = $conn->prepare('SELECT user_id, username, full_name, password FROM user_database WHERE email = ? AND role = ?');
    $role = 'Admin';
    $stmt->bind_param('ss', $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        // Check if user has an active session (has a token)
        $check_active = $conn->prepare("SELECT session_token FROM user_database WHERE user_id = ?");
        $check_active->bind_param('s', $user['user_id']);
        $check_active->execute();
        $res = $check_active->get_result();
        $row = $res->fetch_assoc();

        // Only show error if there's an active session (token exists)
        // Admin bypass: allow this login even if a session_token exists for the admin account
        $ADMIN_BYPASS_USER_ID = 'ADMIN001';
        if (!empty($row['session_token']) && ($user['user_id'] ?? '') !== $ADMIN_BYPASS_USER_ID) {
          $error = 'Your previous session is still active on another device. Please wait or log out from the other device.';
        } else {
          // Clear any old token and create new one
          $session_token = bin2hex(random_bytes(32));
          $update_token = $conn->prepare("UPDATE user_database SET session_token = ?, last_active = CURRENT_TIMESTAMP WHERE user_id = ?");
          $update_token->bind_param('ss', $session_token, $user['user_id']);
          $update_token->execute();

          $_SESSION['session_token'] = $session_token;
          $_SESSION['user_id'] = $user['user_id'];
          $_SESSION['username'] = $user['username'];
          $_SESSION['full_name'] = $user['full_name'];
          $_SESSION['role'] = 'Admin';
          
          include_once __DIR__ . '/../../api/log_audit.php';
          log_activity($conn, $user['user_id'], $user['full_name'] ?? $user['username'], 'Admin', 'System', 'Login', 'Success', 'Auth', 'SESSION');
          
          $token = 'admin_' . $session_token;
          echo '<script>localStorage.setItem("userToken", "' . $token . '"); localStorage.setItem("userRole", "Admin"); localStorage.setItem("loginTime", new Date().getTime()); window.location.replace("/DORCFSRF/pages/admin_dashboard/dashboard.php");</script>';
          exit;
        }
      }
    }

    if (empty($error)) {
      include_once __DIR__ . '/../../api/log_audit.php';
      log_activity($conn, 'UNKNOWN', $email, 'Admin', 'System', 'Failed Login Attempt', 'Failed', 'Auth', 'FAILED');
      $error = 'Invalid email address or password.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Page</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="/DORCFSRF/css/style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
</head>

<body>
  <div class="login-container">
    <div class="logoqeci">
      <a href="index.php">
        <img src="/DORCFSRF/assets/QECI LOGO.jpg " alt="School Logo" class="logo" />
      </a>
    </div>
    <?php if ($error): ?>
      <div class="alert error"
        style="color:#b71c1c; background:#fdecea; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    <form id="loginForm" method="POST" action="admin.php">
      <h3 class="student">Admin</h3>
      <input type="email" id="email" name="email" placeholder="Email Address" required
        value="<?php echo htmlspecialchars($email); ?>" />
      <div class="wrapper">
        <input type="password" id="password" name="password" placeholder="Password" required />
        <span class="show-btn" id="togglePassword" style="cursor: pointer;">
          <i class="fas fa-eye"></i>
        </span>
      </div>
      <button type="submit">Login</button>
    </form>
    <div class="extra">
      <p class="paragraph">
        Forgot your password? <a href="/DORCFSRF/pages/click_button/forgot.php">Reset</a>
      </p>
      <p>New here? <a href="register_firebase.php">Create Account</a></p>
    </div>
  </div>
</body>
<script src="/DORCFSRF/pages/script/Log-in/admin.js"></script>

</html>