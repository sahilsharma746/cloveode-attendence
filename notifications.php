<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';

$auth = new Auth();
$auth->requireAuth();
$storage = new Storage();

$user = $auth->getUser();
$notifications = $storage->getNotifications(null, true);

$_SESSION['notifications_last_viewed'] = date('Y-m-d H:i:s');

function getNotificationIcon($type) {
    switch($type) {
        case 'info':
            return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        case 'warning':
            return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
        case 'success':
            return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        case 'important':
            return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
        default:
            return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>';
    }
}

function getNotificationColor($type) {
    switch($type) {
        case 'info':
            return 'notification-info';
        case 'warning':
            return 'notification-warning';
        case 'success':
            return 'notification-success';
        case 'important':
            return 'notification-important';
        default:
            return 'notification-info';
    }
}

$pageTitle = 'Updates & Notifications';
$showSidebar = true;
include __DIR__ . '/includes/header.php';
?>
<main class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Updates & Notifications</h1>
                <p class="page-subtitle">Stay informed about the latest updates and announcements</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Latest Updates
                </h3>
            </div>
            <div class="card-content">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 64px; height: 64px; color: #94a3b8; margin-bottom: 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <p>No notifications available</p>
                        <p class="text-muted">Check back later for updates</p>
                    </div>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo getNotificationColor($notification['type']); ?>">
                                <div class="notification-icon">
                                    <?php echo getNotificationIcon($notification['type']); ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-header">
                                        <h4 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                        <span class="notification-type badge badge-outline capitalize"><?php echo $notification['type']; ?></span>
                                    </div>
                                    <p class="notification-message"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                    <div class="notification-footer">
                                        <span class="notification-date">
                                            <?php 
                                            $createdAt = new DateTime($notification['created_at']);
                                            $now = new DateTime();
                                            $diff = $now->diff($createdAt);
                                            
                                            if ($diff->days == 0) {
                                                if ($diff->h == 0) {
                                                    echo $diff->i . ' minutes ago';
                                                } else {
                                                    echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                                                }
                                            } elseif ($diff->days == 1) {
                                                echo 'Yesterday';
                                            } elseif ($diff->days < 7) {
                                                echo $diff->days . ' days ago';
                                            } else {
                                                echo $createdAt->format('M d, Y');
                                            }
                                            ?>
                                        </span>
                                        <?php if ($notification['created_by_name']): ?>
                                            <span class="notification-author">by <?php echo htmlspecialchars($notification['created_by_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<style>
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.notification-item {
    display: flex;
    gap: 16px;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid;
    background: var(--bg);
    box-shadow: var(--shadow);
    transition: all 0.2s;
}

.notification-item:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.notification-item.notification-info {
    border-left-color: #3b82f6;
    background: linear-gradient(to right, rgba(59, 130, 246, 0.05), var(--bg));
}

.notification-item.notification-warning {
    border-left-color: #f59e0b;
    background: linear-gradient(to right, rgba(245, 158, 11, 0.05), var(--bg));
}

.notification-item.notification-success {
    border-left-color: #10b981;
    background: linear-gradient(to right, rgba(16, 185, 129, 0.05), var(--bg));
}

.notification-item.notification-important {
    border-left-color: #ef4444;
    background: linear-gradient(to right, rgba(239, 68, 68, 0.05), var(--bg));
}

.notification-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(59, 130, 246, 0.1);
}

.notification-item.notification-info .notification-icon {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.notification-item.notification-warning .notification-icon {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.notification-item.notification-success .notification-icon {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.notification-item.notification-important .notification-icon {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.notification-icon svg {
    width: 24px;
    height: 24px;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    gap: 12px;
}

.notification-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin: 0;
}

.notification-message {
    color: var(--text-muted);
    line-height: 1.6;
    margin: 8px 0;
    white-space: pre-wrap;
}

.notification-footer {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.notification-date {
    font-size: 12px;
    color: var(--text-muted);
}

.notification-author {
    font-size: 12px;
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .notification-item {
        flex-direction: column;
    }
    
    .notification-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
