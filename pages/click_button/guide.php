<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Account Setting</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

</head>

<body>
    <div class="container">
    <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"class="active"></i> <i class="bx bxs-dashboard"> </i> Dashboard</a>
        <a href="/DORCFSRF/pages/student_dashboard/forms.php"><i class="bx bx-notepad"></i> Form</a>
        <a href="/DORCFSRF/pages/student_dashboard/clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
        <a href="/DORCFSRF/pages/student_dashboard/payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
        <a href="/DORCFSRF/pages/student_dashboard/completion.php"><i class="bx bxs-calendar"></i> Completion Report</a>
        <p>SETTINGS</p>
        <a href="/DORCFSRF/pages/student_dashboard/settings.php"><i class="bx bx-cog"></i> Account Settings</a>
      </nav>
    </aside>
        <div class="content-wrapper">
            <header class="header">
                <div class="">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 3 10.5a7.5 7.5 0 0 0 13.65 6.15z" stroke="#888"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                    </svg>
                </div>
                <?php include __DIR__ . '/../../components/user_icon.php'; ?>
            </header>

            <main class="main-content">
                <div class="background-overlay-settings"></div>

                <div class="settings-guide-container">
                    <div class="settings-guide-header">
                        <i class="bx bxs-user-circle"></i>
                        <h2>Account Setup Guide</h2>
                        <p>
                            Follow the steps below to complete and manage your student account.
                        </p>
                    </div>

                    <div class="guide-steps">
                        <div class="guide-card">
                            <i class="bx bx-user-plus"></i>
                            <div class="step-number">1.</div>
                            <h3>Upload Your Profile Image</h3>
                            <p>
                                Choose a clear PNG or JPEG image (max 5MB) to complete your profile.
                            </p>
                        </div>

                        <div class="guide-card">
                            <i class="bx bx-info-circle"></i>
                            <div class="step-number">2.</div>
                            <h3>Update Basic Information</h3>
                            <p>
                                Enter your full name, date of birth, gender, and contact information
                                accurately.
                            </p>
                        </div>

                        <div class="guide-card">
                            <i class="bx bxs-graduation"></i>
                            <div class="step-number">3.</div>
                            <h3>Verify Your Course & Year</h3>
                            <p>
                                Enter your full name, date of birth, gender, and contact information
                                accurately.
                            </p>
                        </div>

                        <div class="guide-card wide-card">
                            <i class="bx bx-mobile-alt"></i>
                            <div class="step-number">4.</div>
                            <h3>Set Up Account Credentials</h3>
                            <p>
                                Create a secure username and password that you can easily remember.
                            </p>
                        </div>

                        <div class="guide-card wide-card">
                            <i class="bx bx-save"></i>
                            <div class="step-number">5.</div>
                            <h3>Save Your Changes</h3>
                            <p>
                                Click the *Save* button to ensure your updated information is recorded.
                            </p>
                        </div>
                    </div>

                    <button class="btn get-it-btn"><a href="/DORCFSRF/pages/student_dashboard/settings.php">Got It</a></button>
                </div>
            </main>
        </div>
    </div>
</body>
</html>









