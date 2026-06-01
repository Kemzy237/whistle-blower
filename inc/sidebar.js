// Sidebar Toggle for Mobile
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const mainContent = document.getElementById('mainContent');

function openSidebar() {
    sidebar.classList.add('mobile-open');
    sidebarOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('mobile-open');
    sidebarOverlay.classList.remove('show');
    document.body.style.overflow = '';
}

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', openSidebar);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
}

// Close sidebar on window resize if open
window.addEventListener('resize', function() {
    if (window.innerWidth > 992) {
        closeSidebar();
    }
}); // sidebar.js - Add this to your existing sidebar.js file

// Function to check for new notifications
function checkNotifications() {
    fetch('app/check-notifications.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            const reportsLink = document.getElementById('reportsLink');

            if (data.unread_count > 0) {
                if (badge) {
                    badge.textContent = data.unread_count;
                    badge.classList.add('new');
                    setTimeout(() => {
                        badge.classList.remove('new');
                    }, 500);
                } else {
                    // Create badge if it doesn't exist
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.id = 'notificationBadge';
                    newBadge.textContent = data.unread_count;
                    reportsLink.appendChild(newBadge);
                }

                // Change favicon or page title to indicate new messages
                document.title = '🔔 New Messages - WhistleGuard Admin';

                // Play a subtle sound (optional - requires user interaction first)
                // playNotificationSound();
            } else if (badge) {
                badge.remove();
                document.title = 'All Reports - Admin Panel | WhistleGuard';
            }
        })
        .catch(error => console.log('Notification check failed:', error));
}

// Optional: Play notification sound (requires user interaction first)
let soundEnabled = false;

function playNotificationSound() {
    if (!soundEnabled) return;
    try {
        const audio = new Audio('assets/sounds/notification.mp3');
        audio.volume = 0.3;
        audio.play().catch(e => console.log('Sound play failed:', e));
    } catch (e) { console.log('Sound not supported'); }
}

// Enable sound on first user interaction
document.addEventListener('click', function enableSound() {
    soundEnabled = true;
    document.removeEventListener('click', enableSound);
});

// Check for notifications every 10 seconds
let notificationInterval = setInterval(checkNotifications, 10000);

// Also check immediately on page load
checkNotifications();

// Update last activity on page unload
window.addEventListener('beforeunload', function() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});