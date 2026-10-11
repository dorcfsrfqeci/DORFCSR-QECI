<?php
/**
 * Combined Registration Page
 * Handles both form display and database saving
 */

// Only return JSON if this is a POST request (API call)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    include __DIR__ . '/../../db_connect.php';
    include __DIR__ . '/../../config/FirebaseAuth.php';

    $firebase = new FirebaseAuth($conn);

    $user_id = trim($_POST['user_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $unit_concern = trim($_POST['unit_concern'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (!$user_id || !$full_name || !$email || !$role || !$password) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }

    // Generate username from email (remove domain)
    $username = explode('@', $email)[0];

    // Register user in database (now passing password for local login)
    $result = $firebase->registerUserInDatabase($user_id, $full_name, $email, $role, $username, $password);

    if ($result['success'] && $role === 'Staff' && !empty($unit_concern)) {
        $unitStmt = $conn->prepare('INSERT INTO units_concerned (unit_name, staff_id) VALUES (?, ?)');
        $unitStmt->bind_param('ss', $unit_concern, $user_id);
        $unitStmt->execute();
        $unitStmt->close();
    }

    echo json_encode($result);
    exit;
}

// If not POST, display the HTML form
include_once __DIR__ . '/../../db_connect.php';

$nextStaffId = 'STF001';
$staffResult = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff' ORDER BY user_id DESC LIMIT 1");
if ($staffResult && $staffResult->num_rows > 0) {
    $lastStaff = $staffResult->fetch_assoc()['user_id'];
    $lastNum = (int) substr($lastStaff, 3);
    $nextStaffId = 'STF' . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Firebase Integration</title>
    <link rel="stylesheet" href="/DORCFSRF/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .firebase-status {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }

        .firebase-status.success {
            display: block;
            color: #0d7377;
            background: #cffafe;
            border-left: 4px solid #06b6d4;
        }

        .firebase-status.error {
            display: block;
            color: #b71c1c;
            background: #fdecea;
            border-left: 4px solid #ef5350;
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

        <h2>Create Account</h2>

        <div id="firebaseStatus" class="firebase-status"></div>

        <form id="registerForm" method="POST" action="register_firebase.php">
            <select name="role" id="role" required onchange="updateUserIdPlaceholder()">
                <option value="">Select Role</option>
                <option value="Student">Student</option>
                <option value="Staff">Staff</option>
            </select>

            <select name="unit_concern" id="unit_concern" style="display: none; margin-top: 15px;">
                <option value="">Select Unit Concern</option>
                <option value="Thesis">Thesis</option>
                <option value="Librarian">Librarian</option>
                <option value="Finance Officer">Finance Officer</option>
                <option value="Admission Officer">Admission Officer</option>
                <option value="Lab./ComLab/ ID">Lab./ComLab/ ID</option>
                <option value="School Cashier">School Cashier</option>
                <option value="Property Custodian">Property Custodian</option>
                <option value="Stud. Services Coordin.">Stud. Services Coordin.</option>
                <option value="Scholarship Coordinat.">Scholarship Coordinat.</option>
                <option value="Guidance Counselor">Guidance Counselor</option>
                <option value="Records Officer">Records Officer</option>
                <option value="Registrar">Registrar</option>
                <option value="College Dean">College Dean</option>
            </select>

            <input type="text" name="user_id" id="user_id" placeholder="User ID" required>
            <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
            <input type="email" name="email" id="email" placeholder="Email Address" required>

            <div class="wrapper">
                <input type="password" name="password" id="password" placeholder="Password (8+ chars)" required
                    minlength="8">
                <span class="show-btn" onclick="togglePasswordVisibility('password', this)">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="wrapper">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password"
                    required minlength="8">
                <span class="show-btn" onclick="togglePasswordVisibility('confirm_password', this)">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <button type="submit">Create Account</button>
        </form>

        <div class="extra">
            <p>Already have an account? <a href="student.php">Login here</a></p>
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

        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        const nextIds = {
            'Staff': '<?php echo $nextStaffId; ?>'
        };

        function updateUserIdPlaceholder() {
            const role = document.getElementById('role').value;
            const userIdField = document.getElementById('user_id');
            const unitConcernSelect = document.getElementById('unit_concern');

            if (role === 'Staff') {
                userIdField.value = nextIds['Staff'];
                userIdField.readOnly = true;
                userIdField.placeholder = 'Staff ID';
                unitConcernSelect.style.display = 'block';
                unitConcernSelect.required = true;
            } else if (role === 'Student') {
                userIdField.value = '';
                userIdField.readOnly = false;
                userIdField.placeholder = 'Student ID (e.g., 2024-001)';
                unitConcernSelect.style.display = 'none';
                unitConcernSelect.required = false;
            } else {
                userIdField.value = '';
                userIdField.readOnly = false;
                userIdField.placeholder = 'User ID';
                unitConcernSelect.style.display = 'none';
                unitConcernSelect.required = false;
            }
        }

        function togglePasswordVisibility(fieldId, button) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                button.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                field.type = 'password';
                button.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const role = document.getElementById('role').value;
            const userId = document.getElementById('user_id').value.trim();
            const fullName = document.getElementById('full_name').value.trim();

            // Client-side validation
            if (password !== confirmPassword) {
                showStatus('Passwords do not match.', 'error');
                return;
            }

            if (password.length < 8) {
                showStatus('Password must be at least 8 characters.', 'error');
                return;
            }

            if (!email || !userId || !fullName || !role) {
                showStatus('Please fill in all fields.', 'error');
                return;
            }

            showStatus('Creating account in Firebase...', 'loading');

            try {
                // Create user in Firebase
                const userCredential = await auth.createUserWithEmailAndPassword(email, password);

                // Now submit the form to save in database
                showStatus('Saving user data to database...', 'loading');

                const formData = new FormData(document.getElementById('registerForm'));
                // Explicitly set values in case browser/Firebase cleared the inputs
                formData.set('password', password);
                formData.set('user_id', userId);
                const response = await fetch('register_firebase.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showStatus('✓ Account created successfully! Redirecting to login...', 'success');
                    setTimeout(() => {
                        if (role === 'Staff') {
                            window.location.href = 'staff.php';
                        } else {
                            window.location.href = 'student.php';
                        }
                    }, 2000);
                } else {
                    showStatus('Error saving to database: ' + result.message, 'error');
                    // Delete Firebase user if database save failed
                    await userCredential.user.delete();
                }

            } catch (error) {
                console.error('Error:', error);
                let errorMessage = 'Registration failed: ' + error.message;

                if (error.code === 'auth/email-already-in-use') {
                    errorMessage = 'This email is already registered.';
                } else if (error.code === 'auth/weak-password') {
                    errorMessage = 'Password is too weak. Use uppercase, lowercase, numbers.';
                } else if (error.code === 'auth/invalid-email') {
                    errorMessage = 'Invalid email address.';
                }

                showStatus(errorMessage, 'error');
            }
        });

        function showStatus(message, type) {
            const statusDiv = document.getElementById('firebaseStatus');
            statusDiv.textContent = message;
            statusDiv.className = 'firebase-status ' + type;
        }
    </script>
</body>

</html>