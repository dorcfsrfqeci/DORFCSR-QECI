<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}
include __DIR__ . '/../../db_connect.php';

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'];

// Get stored profile image based on role
if ($role === 'Staff') {
    $profile_image = '/DORCFSRF/assets/staff_profiles/' . $user_id . '_profile.jpg';
    $profile_image_alt = '/DORCFSRF/assets/staff_profiles/' . $user_id . '_profile.png';
    $profile_image_alt2 = '/DORCFSRF/assets/staff_profiles/' . $user_id . '_profile.gif';
} else {
    $profile_image = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.jpg';
    $profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.png';
    $profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.gif';
}

// Check which image exists
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2;
} else {
    $profile_image_url = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"><i class="bx bxs-dashboard"> </i>
                    Dashboard</a>
                <a href="/DORCFSRF/pages/student_dashboard/forms.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/student_dashboard/clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="/DORCFSRF/pages/student_dashboard/payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="/DORCFSRF/pages/student_dashboard/record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record
                    Status</a>
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
                <div class="user-icon">
                    <i class="bx bx-bell"></i>
                    <i class="bx bx-globe bx-flip-vertical"></i>
                    <div class="user-menu-container">
                        <div class="profile-icon-wrapper" id="profile-toggle">
                            <?php if ($profile_image_url): ?>
                                <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                            <?php else: ?>
                                <i class="bx bxs-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-card" id="profile-dropdown">
                            <div class="card-header-bg"></div>
                            <div class="profile-img">
                                <?php if ($profile_image_url): ?>
                                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                                <?php else: ?>
                                    <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                                <p><?php echo htmlspecialchars($role); ?></p>
                            </div>
                            <div class="card-actions">
                                <button class="action-btn">Profile</button>
                                <button class="action-btn">Sign Out</button>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                    </div>
                </div>
            </header>
            <main class="main-content">
                <div class="about-container">
                    <img class="about-logo" src="/DORCFSRF/assets/QECI LOGO.jpg" alt="QECI logo">
                    <div class="about-title">About Us</div>

                    <div class="section">
                        <h3>About QECI</h3>
                        <p>
                            Quezon Educational Center, Inc. (QECI) is an educational institution committed
                            to providing quality education and holistic development to its students.
                            The school continuously adopts innovative solutions to improve academic
                            services and student experience.
                        </p>
                    </div>

                    <div class="section">
                        <h3>About the System</h3>
                        <p>
                            The Online Request and Clearance for Scholastic Records System is developed
                            to provide a faster, more convenient, and organized way of requesting
                            academic documents such as transcripts, certificates, and clearances
                            for students of QECI.
                            <br><br>
                            This system minimizes manual processing, reduces waiting time, and ensures
                            secure handling of scholastic records through an online platform.
                        </p>
                    </div>

                    <div class="team-section">
                        <div class="team-title">Development Team</div>

                        <div class="team-wrapper">
                            <div class="arrow" id="prev">&#8249;</div>

                            <div class="team-viewport">
                                <div class="team-cards" id="teamCards">
                                    <!-- CARD 1 -->
                                    <div class="team-card">
                                        <img src="/DORCFSRF/assets/student_profiles/gemwil.png">
                                        <h4>Alegre, Gemwil M.</h4>
                                        <span>Programmer</span>
                                    </div>

                                    <!-- CARD 2 -->
                                    <div class="team-card">
                                        <img src="/DORCFSRF/assets/student_profiles/dave.png">
                                        <h4>Formaran, Dave</h4>
                                        <span>Documentator</span>
                                    </div>

                                    <!-- CARD 3 -->
                                    <div class="team-card">
                                        <img src="/DORCFSRF/assets/student_profiles/isabel.png">
                                        <h4>Hita, Isabel Maryniza</h4>
                                        <span>System Analyst</span>
                                    </div>

                                    <!-- CARD 4 -->
                                    <div class="team-card">
                                        <img src="/DORCFSRF/assets/student_profiles/johncarl.png">
                                        <h4>Manalo, John Carlo Louis S.</h4>
                                        <span>Documentator</span>
                                    </div>

                                    <!-- CARD 5 -->
                                    <div class="team-card">
                                        <img src="/DORCFSRF/assets/student_profiles/margel.png">
                                        <h4>Saavedra, Margel B.</h4>
                                        <span>Programmer</span>
                                    </div>

                                    <!-- CARD 6 -->
                                    <div class="team-card">
                                        <img src="/DORCFSRF/assets/student_profiles/Ian.png">
                                        <h4>Vargas,Eric Ian S.</h4>
                                        <span>Programmer</span>
                                    </div>
                                </div>
                            </div>

                            <div class="arrow" id="next">&#8250;</div>
                        </div>
                    </div>

                    <div class="section contact-section">
                        <h3>Contact Us</h3>
                        <p>Need help or share feedback? Contact Us at <a href="mailto:dorcfsrf.qeci@gmail.com" class="contact-email">dorcfsrf.qeci@gmail.com</a></p>
                    </div>
                </div>

            </main>
        </div>
    </div>
    <script>
        const cards = document.getElementById("teamCards");
        const prevBtn = document.getElementById("prev");
        const nextBtn = document.getElementById("next");

        const cardWidth = 178; // includes card width + gap/margin
        const visibleCards = 3;
        const totalCards = 6;
        const maxIndex = totalCards - visibleCards;

        let currentIndex = 0;

        function updateSlider() {
            // Fix: Use backticks and wrap translateX in a string
            cards.style.transform = `translateX(-${currentIndex * cardWidth}px)`;

            // Update button states
            prevBtn.classList.toggle("disabled", currentIndex === 0);
            nextBtn.classList.toggle("disabled", currentIndex === maxIndex);

            // Accessibility tip: disable the actual button functionality
            prevBtn.disabled = (currentIndex === 0);
            nextBtn.disabled = (currentIndex === maxIndex);
        }

        prevBtn.addEventListener("click", () => {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        });

        nextBtn.addEventListener("click", () => {
            if (currentIndex < maxIndex) {
                currentIndex++;
                updateSlider();
            }
        });

        // Initialize the slider state
        updateSlider();
    </script>

</body>

</html>










