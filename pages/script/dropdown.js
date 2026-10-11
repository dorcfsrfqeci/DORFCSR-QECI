const profileToggle = document.getElementById('profile-toggle');
const profileDropdown = document.getElementById('profile-dropdown');

// Toggle profile dropdown safely if elements exist
if (profileToggle && profileDropdown) {
  profileToggle.setAttribute('aria-haspopup', 'true');
  profileToggle.setAttribute('aria-expanded', 'false');

  profileToggle.addEventListener('click', (e) => {
    e.stopPropagation(); // Prevents click from bubbling up
    const isOpen = profileDropdown.classList.toggle('show');
    profileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  // Close menu if clicking outside of it
  window.addEventListener('click', () => {
    if (profileDropdown.classList.contains('show')) {
      profileDropdown.classList.remove('show');
      profileToggle.setAttribute('aria-expanded', 'false');
    }
  });

  // Prevent closing if clicking inside the card
  profileDropdown.addEventListener('click', (e) => {
    e.stopPropagation();
  });
}

// Handle action buttons (Profile and Sign Out)
const actionButtons = document.querySelectorAll('.action-btn');
if (actionButtons.length > 0) {
  // Profile button
  const profileBtn = actionButtons[0];
  profileBtn.addEventListener('click', (e) => {
    e.preventDefault();
    // Check the current page to determine which settings page to go to
    const currentPath = window.location.pathname;
    if (currentPath.includes('student_dashboard')) {
      window.location.href = '/DORCFSRF/pages/student_dashboard/settings.php';
    } else if (currentPath.includes('staff_dashboard')) {
      window.location.href = '/DORCFSRF/pages/staff_dashboard/staff_account_settings.php';
    } else if (currentPath.includes('admin_dashboard')) {
      window.location.href = '/DORCFSRF/pages/admin_dashboard/account_settings.php';
    }
  });

  // Sign Out button
  const signOutBtn = actionButtons[1];
  signOutBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
      window.location.href = '/DORCFSRF/pages/logout.php';
    }
  });
}

// --- Session Keep-Alive ---
// Prevents the PHP session from expiring while the user has the tab open
setInterval(() => {
  fetch('/DORCFSRF/api/keep_alive.php')
    .then(response => response.json())
    .catch(error => console.error('Keep-alive failed:', error));
}, 600000); // Ping every 10 minutes (600,000 ms)

/* Responsive sidebar toggle (mobile) */
(function() {
  function initSidebarToggle() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    // Ensure sidebar has accessible attributes
    if (!sidebar.id) sidebar.id = 'main-sidebar';
    sidebar.setAttribute('role', 'navigation');
    sidebar.setAttribute('aria-label', 'Main navigation');

    // Create overlay
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'sidebar-overlay';
      overlay.setAttribute('role', 'presentation');
      document.body.appendChild(overlay);
    }

    // Create toggle button if not present
    let toggle = document.querySelector('.sidebar-toggle');
    if (!toggle) {
      toggle = document.createElement('button');
      toggle.className = 'sidebar-toggle';
      toggle.setAttribute('aria-label', 'Open menu');
      toggle.setAttribute('aria-controls', sidebar.id);
      toggle.setAttribute('aria-expanded', 'false');
      toggle.innerHTML = '<span style="font-size:20px;">☰</span>';
      // Inline styles to ensure baseline visibility and avoid fragile CSS overrides
      toggle.style.cssText = 'display:none;position:fixed;left:12px;top:12px;z-index:1400;width:44px;height:44px;align-items:center;justify-content:center;border-radius:10px;background:#fff;border:1px solid rgba(0,0,0,0.06);box-shadow:0 6px 18px rgba(2,6,23,0.12);cursor:pointer;';

      // Prefer placing toggle inside the header (if header exists) so it aligns visually
      const header = document.querySelector('.header');
      if (header) {
        // ensure header is positioned relative for absolute positioning
        const computed = window.getComputedStyle(header);
        if (computed.position === 'static') header.style.position = 'relative';
        // adjust style for header placement
        toggle.style.position = 'absolute';
        toggle.style.left = '12px';
        toggle.style.top = '12px';
        toggle.style.zIndex = 1400;
        header.insertBefore(toggle, header.firstChild);
      } else {
        document.body.appendChild(toggle);
      }
    }

    const mq = window.matchMedia('(max-width: 900px)');

    function openSidebar() {
      sidebar.classList.add('open');
      overlay.classList.add('active');
      toggle.classList.add('active');
      toggle.setAttribute('aria-expanded', 'true');
      // prevent background scroll
      document.documentElement.classList.add('no-scroll');
      // focus management: focus first focusable element in sidebar
      const firstFocusable = sidebar.querySelector('a, button, input, [tabindex]:not([tabindex="-1"])');
      if (firstFocusable) {
        firstFocusable.focus();
      }
      // trap focus inside sidebar
      document.addEventListener('keydown', trapFocusHandler);
    }

    function closeSidebar() {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
      toggle.classList.remove('active');
      toggle.setAttribute('aria-expanded', 'false');
      document.documentElement.classList.remove('no-scroll');
      document.removeEventListener('keydown', trapFocusHandler);
      // restore focus to toggle
      toggle.focus();
    }

    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      if (sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    // Close when a nav link is clicked (mobile)
    sidebar.addEventListener('click', function(e) {
      const a = e.target.closest('a');
      if (a && mq.matches) {
        // delay close slightly to allow navigation
        setTimeout(closeSidebar, 120);
      }
    });

    // adjust visibility based on media query
    function update() {
      // Show toggle if media query matches OR window is narrow OR device likely touch
      const isTouch = ('ontouchstart' in window) || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
      const shouldShow = mq.matches || window.innerWidth <= 900 || isTouch;
      if (shouldShow) {
        toggle.style.display = 'flex';
      } else {
        toggle.style.display = 'none';
        closeSidebar();
      }
    }

    // update header offset and place toggle under header when present
    function updateHeaderOffset() {
      const header = document.querySelector('.header');
      const offset = header ? (header.getBoundingClientRect().height + 8) : 12;
      // keep header offset variable for sticky elements
      document.documentElement.style.setProperty('--header-offset', offset + 'px');
      // Position toggle on the left side of the header (inside header area)
      toggle.style.top = '12px';
      toggle.style.left = '12px';
    }

    // Focus trap handler
    function trapFocusHandler(e) {
      if (e.key === 'Escape') {
        closeSidebar();
        return;
      }
      if (e.key !== 'Tab') return;
      const focusable = Array.from(sidebar.querySelectorAll('a, button, input, [tabindex]:not([tabindex="-1"])'))
        .filter(el => !el.hasAttribute('disabled'));
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey) {
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    }

    // update header offset on load/resize
    window.addEventListener('resize', updateHeaderOffset);
    window.addEventListener('orientationchange', updateHeaderOffset);
    window.addEventListener('load', updateHeaderOffset);
    updateHeaderOffset();

    if (mq.addEventListener) mq.addEventListener('change', update); else mq.addListener(update);
    update();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSidebarToggle); else initSidebarToggle();
})();
