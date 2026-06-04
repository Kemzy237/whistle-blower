<?php
// Include database connection for notifications
require_once 'db_connection.php';
require_once 'app/model/index.php';

// Get unread messages count
$unreadReportsCount = getUnreadMessagesCount($conn);
?>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>WhistleGuard</h4>
        <small class="text-muted-custom">Admin Panel</small>
    </div>
    <ul class="sidebar-nav">
        <li><a href="admin-dashboard.php">
            <i class="fas fa-tachometer-alt"></i>Dashboard
        </a></li>
        <li>
            <a href="admin-reports.php" id="reportsLink">
                <i class="fas fa-file-alt"></i>All Reports
                <?php if ($unreadReportsCount > 0): ?>
                <span class="notification-badge" id="notificationBadge">
                    <?php echo $unreadReportsCount; ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
        <li><a href="admin-users.php"><i class="fas fa-users"></i>Profile</a></li>
        <li><a href="admin-settings.php"><i class="fas fa-cog"></i>Settings</a></li>
        <li><a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
    </ul>
</div>