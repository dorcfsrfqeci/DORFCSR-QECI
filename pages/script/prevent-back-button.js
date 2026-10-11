/**
 * Prevent Back Button Navigation
 * Keeps users on their dashboard and only allows logout via Sign Out button
 * VERY AGGRESSIVE - Prevents all back navigation attempts
 */

(function () {
    // Push state to create a new history entry
    history.pushState(null, null, window.location.href);

    // Handle back button attempts
    window.addEventListener('popstate', function (event) {
        // Immediately push state again to block the back navigation
        history.pushState(null, null, window.location.href);
        event.preventDefault();
    });

    // Additional protection: monitor history state changes
    let lastUrl = window.location.href;

    // Block ALL navigation attempts except logout
    window.addEventListener('beforeunload', function (event) {
        // Allow only logout links to navigate away
        if (event.target && event.target.href && event.target.href.includes('logout')) {
            return;
        }
    });



    // Intercept all link clicks except logout
    document.addEventListener('click', function (e) {
        const target = e.target.closest('a');
        if (target && target.href) {
            // Allow logout links
            if (target.href.includes('logout')) {
                return true;
            }
            // Allow external links (new tab/window)
            if (target.target === '_blank' || target.target === '_new') {
                return true;
            }
            // Allow back button in header if it's intentional navigation
            if (target.classList.contains('back-to-dashboard') || target.classList.contains('back-btn')) {
                // Check if it's going to dashboard
                if (target.href.includes('dashboard') || target.href.includes('pages/')) {
                    return true;
                }
            }
        }
    }, true);
})();
