/**
 * Session Management Script
 * Handles automatic logout when user navigates away or closes browser
 * Include this in all protected/dashboard pages
 */

(function () {
    // Get the current user's session token from PHP-set data
    const sessionToken = document.querySelector('meta[name="session-token"]')?.content;
    const userId = document.querySelector('meta[name="user-id"]')?.content;

    // Function to clear session token from database
    async function clearSessionToken() {
        if (!sessionToken || !userId) return;

        try {
            // Make a request to clear the session token
            fetch('/DORCFSRF/api/clear-session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_token: sessionToken,
                    user_id: userId
                }),
                // Don't wait for response - just fire and forget
                keepalive: true
            }).catch(() => {
                // Ignore errors - user is already leaving
            });
        } catch (error) {
            // Silently fail - don't interrupt user navigation
        }
    }

    // Clear session when user closes tab/window
    window.addEventListener('beforeunload', function () {
        // Only clear if user is actually leaving (not just refreshing)
        if (performance.navigation.type === 1) {
            // Page refresh - don't clear
            return;
        }
        clearSessionToken();
    });

    // Also clear on page visibility change (when tab loses focus for extended period)
    let visibilityTimeout;
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            // Tab is hidden - set timeout to clear session if user doesn't return
            visibilityTimeout = setTimeout(() => {
                clearSessionToken();
            }, 60000); // Clear after 1 minute of inactivity
        } else {
            // Tab is visible again - cancel the timeout
            if (visibilityTimeout) {
                clearTimeout(visibilityTimeout);
            }
        }
    });

    // Update last_active timestamp periodically while on the page
    setInterval(function () {
        if (!userId) return;

        fetch('/DORCFSRF/api/update-last-active.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId
            })
        }).catch(() => {
            // Ignore errors
        });
    }, 300000); // Update every 5 minutes
})();
