<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password</title>
    <link rel="stylesheet" href="/DORCFSRF/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        .firebase-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .firebase-message.success {
            display: block;
            color: #0d7377;
            background: #cffafe;
            border: 1px solid #06b6d4;
        }
        .firebase-message.error {
            display: block;
            color: #b71c1c;
            background: #fdecea;
            border: 1px solid #ef5350;
        }
        .firebase-message.loading {
            display: block;
            color: #1565c0;
            background: #e3f2fd;
            border: 1px solid #42a5f5;
        }
        .loader {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        .loader.show {
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .form-info {
            font-size: 14px;
            color: #666;
            margin-top: 12px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
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

        <h1>Reset Password</h1>

        <div id="message" class="firebase-message">
            <span class="loader" id="loader"></span>
            <span id="messageText"></span>
        </div>

        <form id="resetForm">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your registered email address"
                required
            />

            <button type="submit" id="submitBtn">
                <span id="btnText">Send Reset Link</span>
            </button>
        </form>

        <div class="form-info">
            <strong>How it works:</strong><br>
            1. Enter your registered email address<br>
            2. We'll send you a password reset link<br>
            3. Click the link in the email to reset your password<br>
            4. Create a new password and you're done!
        </div>

        <div class="extra">
            <p>Remembered your password? <a href="/DORCFSRF/pages/login_form/student.php">Back to Login</a></p>
        </div>
    </div>

    <!-- Firebase SDK - Using compat version for easier syntax -->
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

        // Set language preference
        auth.languageCode = 'en';

        // Get DOM elements
        const resetForm = document.getElementById('resetForm');
        const emailInput = document.getElementById('email');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const messageDiv = document.getElementById('message');
        const messageText = document.getElementById('messageText');
        const loader = document.getElementById('loader');

        // Handle form submission
        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = emailInput.value.trim();

            if (!email) {
                showMessage('Please enter your email address.', 'error');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Sending...';
            loader.classList.add('show');
            showMessage('Verifying your email address...', 'loading');

            try {
                // First, verify email exists in the database
                const verifyResponse = await fetch('/DORCFSRF/api/verify_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });

                const verifyData = await verifyResponse.json();

                if (!verifyData.exists) {
                    showMessage('✗ No account found with this email address.', 'error');
                    submitBtn.disabled = false;
                    btnText.textContent = 'Send Reset Link';
                    loader.classList.remove('show');
                    return;
                }

                // Email exists, send password reset email
                showMessage('Sending reset link to your email...', 'loading');
                await auth.sendPasswordResetEmail(email);

                // Success message
                showMessage(
                    '✓ Password reset link sent! Check your email (including spam folder) for the reset link. The link will expire in 1 hour.',
                    'success'
                );

                // Reset form
                resetForm.reset();
                submitBtn.disabled = false;
                btnText.textContent = 'Send Reset Link';
                loader.classList.remove('show');

            } catch (error) {
                console.error('Error:', error);
                let errorMessage = 'An error occurred. Please try again.';

                // Handle specific Firebase errors
                if (error.code === 'auth/user-not-found') {
                    errorMessage = 'No account found with this email address.';
                } else if (error.code === 'auth/invalid-email') {
                    errorMessage = 'Please enter a valid email address.';
                } else if (error.code === 'auth/too-many-requests') {
                    errorMessage = 'Too many reset requests. Please try again later.';
                } else if (error.code === 'auth/network-request-failed') {
                    errorMessage = 'Network error. Please check your connection.';
                } else {
                    errorMessage = error.message;
                }

                showMessage('✗ ' + errorMessage, 'error');
                submitBtn.disabled = false;
                btnText.textContent = 'Send Reset Link';
                loader.classList.remove('show');
            }
        });

        // Helper function to show messages
        function showMessage(message, type) {
            messageText.textContent = message;
            messageDiv.className = 'firebase-message ' + type;
        }

        // Check if user is already logged in (optional)
        auth.onAuthStateChanged((user) => {
            if (user) {
                // User is logged in, redirect to dashboard
                console.log('User is logged in:', user.email);
            }
        });
    </script>
</body>
</html>









