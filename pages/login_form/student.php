<?php
session_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include database connection
$conn = null;
$firebaseAuth = null;

try {
  include __DIR__ . '/../../db_connect.php';
  if (!isset($conn) || !$conn) {
    $conn = new mysqli("localhost", "root", "", "clearance_system_db");
    if ($conn->connect_error) {
      throw new Exception("Database connection failed: " . $conn->connect_error);
    }
  }

  include __DIR__ . '/../../config/FirebaseAuth.php';
  $firebaseAuth = new FirebaseAuth($conn);
} catch (Exception $e) {
  error_log("Student.php error: " . $e->getMessage());
  // Continue without stopping - user can see login form but may not be able to submit
}

$error = '';
$studentID = '';
$email = '';

// Handle Firebase authentication callback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firebase_uid'])) {
  $firebase_uid = trim($_POST['firebase_uid'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $studentID = trim($_POST['studentID'] ?? '');

  if ($firebase_uid === '' || $email === '' || $studentID === '') {
    $error = 'Please fill out all fields.';
  } else {
    // Fetch user from database
    $user = $firebaseAuth->getUserByEmail($email);

    if ($user && strtolower($user['user_id']) === strtolower($studentID)) {
      // Always allow login. Generate a new token, which inherently invalidates any previous session on other devices.
      $session_token = bin2hex(random_bytes(32));
      $update_token = $conn->prepare("UPDATE user_database SET session_token = ?, last_active = CURRENT_TIMESTAMP WHERE user_id = ?");
      $update_token->bind_param('ss', $session_token, $user['user_id']);
      $update_token->execute();

      $_SESSION['session_token'] = $session_token;
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['full_name'] = $user['full_name'];
      $_SESSION['role'] = 'Student';
      $_SESSION['firebase_uid'] = $firebase_uid;

      include_once __DIR__ . '/../../api/log_audit.php';
      log_activity($conn, $user['user_id'], $user['full_name'] ?? $user['username'], 'Student', 'System', 'Login', 'Success', 'Auth', 'SESSION');

      // Check if student form exists
      $checkForm = $conn->prepare('SELECT 1 FROM student_information_database WHERE student_id = ? LIMIT 1');
      $checkForm->bind_param('s', $user['user_id']);
      $checkForm->execute();
      $checkForm->store_result();

      if ($checkForm->num_rows > 0) {
        header('Location: /DORCFSRF/pages/student_dashboard/dashboard.php');
      } else {
        header('Location: /DORCFSRF/pages/student_dashboard/forms.php');
      }
      exit;
    } else {
      $error = 'User not found or Student ID does not match.';
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
  <link rel="stylesheet" href="/DORCFSRF/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    .firebase-status {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 16px;
      display: none;
    }

    .firebase-status.loading {
      display: block;
      color: #1565c0;
      background: #e3f2fd;
      border-left: 4px solid #42a5f5;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="logoqeci">
      <a href="index.php">
        <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="School Logo" class="logo" />
      </a>
    </div>
    <?php if ($error): ?>
      <div class="alert error"
        style="color:#b71c1c; background:#fdecea; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <div id="firebaseStatus" class="firebase-status">
      <span
        style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #1565c0; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 10px; vertical-align: middle;"></span>
      Verifying with Firebase...
    </div>

    <form id="loginForm" method="POST" action="student.php">
      <h3 class="student">Student</h3>
      <input type="text" id="studentID" name="studentID" placeholder="Student ID" required
        value="<?php echo htmlspecialchars($studentID); ?>" />
      <input type="email" id="email" name="email" placeholder="Email Address" required
        value="<?php echo htmlspecialchars($email); ?>" />
      <div class="wrapper">
        <input type="password" id="password" name="password" placeholder="Password" required />
        <span class="show-btn">
          <i class="fas fa-eye"></i>
        </span>
      </div>
      <input type="hidden" id="firebase_uid" name="firebase_uid" />
      <button type="submit" id="loginBtn">Login</button>
    </form>
    <div class="extra">
      <p class="paragraph">
        Forgot your password? <a href="/DORCFSRF/pages/click_button/forgot.php">Reset</a>
      </p>
      <p>New here? <a href="register_firebase.php">Create Account</a></p>
    </div>
  </div>

  <!-- Firebase SDK -->
  <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>

  <script>
    // Firebase Configuration
    const firebaseConfig = {
      apiKey: "AIzaSyAz1GXAfn2zj3GqUqSBIcN3XnUuSXGCJdc",
      authDomain: "dorfcsrfqeci.firebaseapp.com",
      projectId: "dorfcsrfqeci",
      storageBucket: "dorfcsrfqeci.firebasestorage.app",
      messagingSenderId: "822546813632",
      appId: "1:822546813632:web:35a54d6daf0a6fdb9b5325"
    };

    firebase.initializeApp(firebaseConfig);
    const auth = firebase.auth();

    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const firebaseUidInput = document.getElementById('firebase_uid');
    const firebaseStatus = document.getElementById('firebaseStatus');
    const loginBtn = document.getElementById('loginBtn');

    // Password visibility toggle
    document.querySelector('.show-btn').addEventListener('click', function () {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        passwordInput.type = 'password';
        this.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });

    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const email = emailInput.value.trim();
      const password = passwordInput.value;

      if (!email || !password) {
        alert('Please enter email and password');
        return;
      }

      firebaseStatus.style.display = 'block';
      loginBtn.disabled = true;

      try {
        // Authenticate with Firebase
        const userCredential = await auth.signInWithEmailAndPassword(email, password);
        const user = userCredential.user;

        // Store Firebase UID and submit form
        firebaseUidInput.value = user.uid;  // Delay to show success message
        firebaseStatus.innerHTML = '<span style="display: inline-block; color: #0d7377;">✓ Authentication successful!</span>';
        firebaseStatus.style.color = '#0d7377';
        firebaseStatus.style.background = '#cffafe';
        firebaseStatus.style.borderLeft = '4px solid #06b6d4';

        setTimeout(() => {
          // Store authentication token in localStorage
          localStorage.setItem("userToken", "student_" + user.uid);
          localStorage.setItem("userRole", "Student");
          localStorage.setItem("loginTime", new Date().getTime());
          loginForm.submit();
        }, 1000);

      } catch (error) {
        console.error('Firebase error:', error);
        loginBtn.disabled = false;
        firebaseStatus.style.display = 'none';

        let errorMessage = 'Login failed: ' + error.message;
        if (error.code === 'auth/user-not-found') {
          errorMessage = 'No account found with this email.';
        } else if (error.code === 'auth/wrong-password') {
          errorMessage = 'Incorrect password.';
        } else if (error.code === 'auth/invalid-email') {
          errorMessage = 'Invalid email address.';
        }

        alert(errorMessage);
      }
    });
  </script>

  <style>
    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>
</body>

</html>