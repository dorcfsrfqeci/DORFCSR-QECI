<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms - Submission Complete</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/DORCFSRF/css/forms.css" />
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
                <div id="submission-container">
                    <div class="submission-modal-overlay">
                        <div class="submission-modal">
                            <div class="submission-content">
                                <h2>Submission Complete!</h2>
                                <p>Your request has been sent to the Record's Office. Kindly monitor your account for
                                    further instructions.</p>
                                <button class="continue-btn"><a href="../../DORCFSRF/pages/student_dashboard/clearance.php">Continue</a></button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
      // Check if submission is on cooldown
      const cooldownHours = 72; // 3 days
      
      function canSubmitForm() {
        const lastSubmit = localStorage.getItem('studentFormLastSubmit');
        if (!lastSubmit) return true;
        const last = new Date(parseInt(lastSubmit, 10));
        const now = new Date();
        const diff = (now - last) / (1000 * 60 * 60);
        return diff >= cooldownHours;
      }

      function getCooldownMessage() {
        const lastSubmit = localStorage.getItem('studentFormLastSubmit');
        if (!lastSubmit) return '';
        const last = new Date(parseInt(lastSubmit, 10));
        const now = new Date();
        const diffMs = (last.getTime() + cooldownHours * 60 * 60 * 1000) - now.getTime();
        if (diffMs <= 0) return '';
        
        const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.ceil((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        
        let timeStr = '';
        if (days > 0) timeStr += days + ' day(s) ';
        if (hours > 0) timeStr += hours + ' hour(s) ';
        if (mins > 0) timeStr += mins + ' minute(s)';
        
        return `You can submit another request in ${timeStr.trim()}.`;
      }

      document.addEventListener('DOMContentLoaded', function() {
        if (!canSubmitForm()) {
          const msg = getCooldownMessage();
          const container = document.getElementById('submission-container');
          container.innerHTML = `
            <div class="submission-modal-overlay">
              <div class="submission-modal">
                <div class="submission-content">
                  <h2>Submission Blocked</h2>
                  <p>${msg}</p>
                  <button class="continue-btn" onclick="window.location.href='../../DORCFSRF/pages/student_dashboard/forms.php'">Back to Form</button>
                </div>
              </div>
            </div>
          `;
        }
      });
    </script>









