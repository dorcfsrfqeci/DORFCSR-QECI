<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms - Submission Complete</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/DORCFSRF/css/forms.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="container">
    <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"class="active"><i class="bx bxs-dashboard"> </i> Dashboard</a>
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
                <div class="submission-modal-overlay">
                    <div class="submission-modal">
                        <div class="submission-content">
                            <i class="fa-solid fa-diamond-turn-right"></i>
                            <h2>Redirecting to Gcash Online Payment</h2>
                            <p>Please wait while securely connect you to the payment gateway.</p>
                            <a href="/DORCFSRF/pages/student_dashboard/payment.php">
                            <button class="continue-btn">Back</button>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>









