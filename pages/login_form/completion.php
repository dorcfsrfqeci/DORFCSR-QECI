<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Completion Report</title>

  <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
  <div class="container">
   <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
        <a href="/DORCFSRF/pages/student_dashboard/forms.php"><i class="bx bx-notepad"></i> Form</a>
        <a href="/DORCFSRF/pages/student_dashboard/clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
        <a href="/DORCFSRF/pages/student_dashboard/payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
        <a href="/DORCFSRF/pages/student_dashboard/record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
        <a href="/DORCFSRF/pages/student_dashboard/completion.php"class="active"><i class="bx bxs-calendar"></i> Completion Report</a>
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
        <div class="user-icon">
          <i class="bx bx-bell"></i>
          <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
          <div class="user-menu-container">
            <i class="bx bxs-user-circle" id="profile-toggle"></i>
            <div class="profile-card" id="profile-dropdown">
              <div class="card-header-bg"></div>
              <img src="/DORCFSRF/assets/Alegre.jpg" alt="Student Profile" class="profile-img">
              <div class="profile-info">
                <h3>Gemwil M. Alegre</h3>
                <p>STUDENT</p>
              </div>
              <div class="card-actions">
                <button class="action-btn">Profile</button>
                <button class="action-btn">Sign Out</button>
              </div>
              <script src="/DORCFSRF/pages/script/dropdown.js">dropdown</script>
            </div>
        </div>
      </header>
      <main class="main-content">
        <div class="background-overlay"></div>
        <h2 class="breadcrumb"><b>Home</b> > Completion Report</h2>

        <div class="report-container">
          <div class="report-header">
            <h2>Quezonian Educational College, Inc.</h2>
            <p>Online Request for Clearance and Scholastic Record System</p>
            <hr />
          </div>

          <section class="report-section">
            <h3>Student Information</h3>
            <div class="info-list">
              <div class="info-item"><label>Name:</label><span>Dela Cruz, Juan Mercado</span></div>
              <div class="info-item"><label>Student I.D.</label><span>1822-00045</span></div>
              <div class="info-item"><label>Course/Year:</label><span>BSCS 4</span></div>
              <div class="info-item"><label>Request Type:</label><span>Transcript of Records</span></div>
            </div>
          </section>

          <section class="report-section">
            <h3>Clearance & Payment Verification</h3>
            <table class="clearance-table">
              <thead>
                <tr>
                  <th>Office</th>
                  <th>Verified By</th>
                  <th>Date</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Library</td>
                  <td>Ms. Lopez</td>
                  <td>01/05/2025</td>
                  <td>Cleared</td>
                </tr>
                <tr>
                  <td>Cashier</td>
                  <td>Mr. Santos</td>
                  <td>01/04/2025</td>
                  <td>Payment Verified</td>
                </tr>
                <tr>
                  <td>Registrar</td>
                  <td>Ms. Dela Peña</td>
                  <td>01/06/2025</td>
                  <td>Approved</td>
                </tr>
              </tbody>
            </table>
          </section>

          <section class="report-section">
            <h3>Document Status</h3>
            <div class="status-box">
              <p><strong>Status:</strong> <span class="status-cleared">Cleared</span> - 10/07/2025 Processed by Admin
                Staff (Ms. Romeo)</p>
              <p><strong>Remarks:</strong> Released to student upon presentation of valid ID.</p>
            </div>
          </section>

          <section class="report-section">
            <h3>Acknowledgement Receipt</h3>
            <div class="info-list">
              <div class="info-item"><label>Received By:</label><span>Ms. Jessica Ramos</span></div>
              <div class="info-item">
                <label>Signature:</label>
                <img src="https://i.imgur.com/g8cTj7S.png" alt="Signature" class="signature-img" />
              </div>
              <div class="info-item"><label>Release Date:</label><span>10/06/2025</span></div>
            </div>
          </section>

          <section class="report-section">
            <h3>Payment Receipt Details</h3>
            <div class="info-list">
              <div class="info-item"><label>OR Number:</label><span>02000121</span></div>
              <div class="info-item"><label>Amount Paid:</label><span>P300.00</span></div>
              <div class="info-item"><label>Date of Payment:</label><span>01/04/2025</span></div>
            </div>
          </section>

          <div class="report-actions">
            <button class="btn-print"><i class="bx bx-printer"></i><a href="/DORCFSRF/pages/click_button/completionprt.php" style="text-decoration: none; color: #333;">Print Report</a></button>
            <button class="btn-download"><i class="bx bx-download"></i><a href="/DORCFSRF/pages/click_button/completiondl.php" style="text-decoration: none; color: #fff;">Download</a> </button>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>
</html>









