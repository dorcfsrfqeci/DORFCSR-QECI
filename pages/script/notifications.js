// Notifications functionality
document.addEventListener('DOMContentLoaded', function() {
    const notifContainer = document.getElementById('notificationContainer');
    const notifBell = document.getElementById('notificationBell');
    const notifBadge = document.getElementById('notificationBadge');
    const notifList = document.getElementById('notificationList');

    if (!notifContainer) return;

    function fetchNotifications() {
        fetch('/DORCFSRF/api/get_notifications.php')
            .then(res => res.json())
            .then(data => {
                if (data.unreadCount > 0) {
                    notifBadge.textContent = data.unreadCount;
                    notifBadge.style.display = 'block';
                } else {
                    notifBadge.style.display = 'none';
                }

                if (data.notifications && data.notifications.length > 0) {
                    notifList.innerHTML = data.notifications.map(n => `
                        <li class="notification-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}" onclick="markNotificationRead(${n.id}, this)">
                            <p>${n.message}</p>
                            <span>${n.created_at}</span>
                        </li>
                    `).join('');
                } else {
                    notifList.innerHTML = '<li class="notification-item" style="text-align:center;">No notifications</li>';
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
    }

    // Toggle dropdown
    notifBell.addEventListener('click', (e) => {
        e.stopPropagation();
        notifContainer.classList.toggle('active');
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!notifContainer.contains(e.target)) {
            notifContainer.classList.remove('active');
        }
    });

    fetchNotifications();
    // Poll every 30 seconds
    setInterval(fetchNotifications, 30000);
});

window.markNotificationRead = function(id, element) {
    if (!element.classList.contains('unread')) return;

    fetch(`/api/mark_notification_read.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('unread');
                let badge = document.getElementById('notificationBadge');
                let count = parseInt(badge.textContent);
                if (count > 1) {
                    badge.textContent = count - 1;
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(err => console.error('Error marking as read:', err));
};
