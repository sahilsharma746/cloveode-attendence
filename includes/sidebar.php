<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/storage.php';
$auth = new Auth();
$user = $auth->getUser();
if (!$user) return;
$isAdmin = $user['role'] === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']);

$storage = new Storage();
$lastViewed = isset($_SESSION['notifications_last_viewed']) ? $_SESSION['notifications_last_viewed'] : null;
$newNotificationsCount = $storage->getNewNotificationsCount(7, $lastViewed);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <!-- <h1 class="sidebar-logo">CLOVEODE TECHNOLOGIES</h1> -->
        <p class="sidebar-subtitle">Attendance System</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/attendance.php" class="nav-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span>My Attendance</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/leaves.php" class="nav-link <?php echo $currentPage === 'leaves.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span>Leave Requests</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/notifications.php" class="nav-link <?php echo $currentPage === 'notifications.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            <span>Updates</span>
            <?php if ($newNotificationsCount > 0 && $currentPage !== 'notifications.php'): ?>
                <span class="nav-notification-badge"><?php echo $newNotificationsCount > 99 ? '99+' : $newNotificationsCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/organization.php" class="nav-link <?php echo $currentPage === 'organization.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <span>Organization</span>
        </a>
        <?php if ($isAdmin): ?>
        <a href="<?php echo BASE_URL; ?>/admin.php" class="nav-link <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Admin Panel</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
            <svg class="theme-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <!-- <span class="theme-toggle-text">Dark Mode</span> -->
        </button>
        <div class="user-info">
            <?php 
            require_once __DIR__ . '/helpers.php';
            echo getUserAvatar($user, 'small');
            ?>
            <div class="user-details">
                <p class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></p>
                <p class="user-role"><?php echo ucfirst($user['role']); ?></p>
            </div>
        </div>
        <button class="logout-btn" onclick="logout()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Sign Out
        </button>
    </div>
</div>
