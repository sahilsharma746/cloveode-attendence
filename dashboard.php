<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/helpers.php';

$auth = new Auth();
$auth->requireAuth();
$storage = new Storage();

$sessionUser = $auth->getUser();
$userId = $sessionUser['id'];
// Fetch fresh user data from database to ensure leave_balance is current
$user = $storage->getUser($userId);
// Update session with fresh data
$auth->updateSessionUser($user);

$attendanceHistory = $storage->getAttendance($userId);
$leaves = $storage->getLeaves($userId);
$holidays = $storage->getUpcomingHolidays(10);
$recentNotifications = $storage->getNotifications(5, true);
$usersOnLeave = $storage->getUsersCurrentlyOnLeave();
$employeesInOffice = $storage->getEmployeesInOffice();
$employeesOnBreak = $storage->getEmployeesOnBreak(); 

$presentDays = count(array_filter($attendanceHistory, fn($a) => $a['status'] === 'present' || $a['status'] === 'out' || ($a['check_out'] && $a['status'] !== 'absent')));
$lateDays = count(array_filter($attendanceHistory, fn($a) => $a['status'] === 'late'));
$pendingLeaves = count(array_filter($leaves, fn($l) => $l['status'] === 'pending'));
$approvedLeaves = count(array_filter($leaves, fn($l) => $l['status'] === 'approved'));

$today = date('Y-m-d');
$todaysAttendance = null;
foreach ($attendanceHistory as $att) {
    if ($att['date'] === $today) {
        $todaysAttendance = $att;
        break;
    }
}
$pageTitle = 'Dashboard';
$showSidebar = true;
include __DIR__ . '/includes/header.php';
?>
<main class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Good Morning, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?> 👋</h1>
                <p class="page-subtitle">Your presence makes progress possible—consistency creates results.</p>
            </div>
            <div class="date-display">
                <p class="date-label">Today's Date</p>
                <p class="date-value"><?php echo date('l, F jS, Y'); ?></p>
            </div>
        </div>
        <div class="stats-grid">
            <div class="stat-card stat-green">
                <div class="stat-icon stat-icon-green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Days Present</p>
                    <p class="stat-value"><?php echo $presentDays; ?></p>
                    <p class="stat-trend trend-up">+2 this week</p>
                </div>
            </div>

            <div class="stat-card stat-orange">
                <div class="stat-icon stat-icon-orange">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Late Arrivals</p>
                    <p class="stat-value"><?php echo $lateDays; ?></p>
                    <p class="stat-trend trend-down">Needs attention</p>
                </div>
            </div>

            <div class="stat-card stat-blue">
                <div class="stat-icon stat-icon-blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Leave Balance</p>
                    <p class="stat-value"><?php echo $user['leave_balance']; ?></p>
                </div>
            </div>

            <div class="stat-card stat-purple">
                <div class="stat-icon stat-icon-purple">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Pending Requests</p>
                    <p class="stat-value"><?php echo $pendingLeaves; ?></p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card status-card">
                <h3 class="card-title">Today's Status</h3>
                <div class="status-content">
                    <div class="status-icon <?php echo $todaysAttendance && $todaysAttendance['check_in'] ? 'status-checked-in' : ''; ?>">
                        <?php if ($todaysAttendance && $todaysAttendance['check_in']): ?>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <?php else: ?>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <?php endif; ?>
                    </div>
                    <div class="status-time">
                        <p class="time-value">
                            <?php 
                            if ($todaysAttendance && $todaysAttendance['check_in']) {
                                $checkInTime = new DateTime($todaysAttendance['check_in'], new DateTimeZone('Asia/Kolkata'));
                                echo $checkInTime->format('g:i A');
                            } else {
                                echo 'Not Checked In';
                            }
                            ?>
                        </p>
                        <p class="time-label">Check In Time</p>
                        <?php if ($todaysAttendance && $todaysAttendance['status'] === 'late' && isset($todaysAttendance['late_minutes']) && $todaysAttendance['late_minutes']): ?>
                            <p class="late-warning" style="color: #ef4444; font-size: 14px; font-weight: 600; margin-top: 8px;">
                                 You are late by <?php echo formatLateTime($todaysAttendance['late_minutes']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($todaysAttendance && $todaysAttendance['check_out']): ?>
                        <div class="status-divider"></div>
                        <div class="status-time">
                            <?php
                            $checkOutTime = new DateTime($todaysAttendance['check_out'], new DateTimeZone('Asia/Kolkata'));
                            ?>
                            <p class="time-value"><?php echo $checkOutTime->format('g:i A'); ?></p>
                            <p class="time-label">Check Out Time</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Upcoming Holidays
                    </h3>
                </div>
                <div class="card-content">
                    <?php if (empty($holidays)): ?>
                        <div class="empty-state">
                            <p>No upcoming holidays</p>
                        </div>
                    <?php else: ?>
                        <div class="holidays-list">
                            <?php foreach ($holidays as $holiday): ?>
                                <div class="holiday-item" style="padding: 12px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <p class="font-medium" style="margin: 0; color: var(--text);"><?php echo htmlspecialchars($holiday['name']); ?></p>
                                        <p class="text-sm text-muted" style="margin: 4px 0 0 0;">
                                            <?php echo date('l, M d, Y', strtotime($holiday['date'])); ?>
                                        </p>
                                        <?php if ($holiday['description']): ?>
                                            <p class="text-xs text-muted" style="margin: 4px 0 0 0;"><?php echo htmlspecialchars($holiday['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    $daysUntil = (strtotime($holiday['date']) - strtotime('today')) / 86400;
                                    if ($daysUntil == 0):
                                    ?>
                                        <span class="badge badge-green" style="margin-left: 12px;">Today</span>
                                    <?php elseif ($daysUntil == 1): ?>
                                        <span class="badge badge-blue" style="margin-left: 12px;">Tomorrow</span>
                                    <?php elseif ($daysUntil > 0 && $daysUntil <= 7): ?>
                                        <span class="badge badge-outline" style="margin-left: 12px;"><?php echo (int)$daysUntil; ?> days</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        Latest Updates
                    </h3>
                    <?php if (!empty($recentNotifications)): ?>
                        <a href="<?php echo BASE_URL; ?>/notifications.php" class="btn btn-outline btn-sm" style="font-size: 12px; padding: 6px 12px;">
                            View All
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <?php if (empty($recentNotifications)): ?>
                        <div class="empty-state">
                            <p>No updates available</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list-compact">
                            <?php foreach ($recentNotifications as $notification): ?>
                                <div class="notification-item-compact notification-<?php echo $notification['type']; ?>">
                                    <div class="notification-icon-compact">
                                        <?php
                                        $icons = [
                                            'info' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                                            'warning' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                                            'success' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                                            'important' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'
                                        ];
                                        echo $icons[$notification['type']] ?? $icons['info'];
                                        ?>
                                    </div>
                                    <div class="notification-content-compact">
                                        <h4 class="notification-title-compact"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                        <p class="notification-message-compact"><?php echo htmlspecialchars(substr($notification['message'], 0, 80)) . (strlen($notification['message']) > 80 ? '...' : ''); ?></p>
                                        <span class="notification-date-compact">
                                            <?php 
                                            $createdAt = new DateTime($notification['created_at']);
                                            $now = new DateTime();
                                            $diff = $now->diff($createdAt);
                                            
                                            if ($diff->days == 0) {
                                                if ($diff->h == 0) {
                                                    echo $diff->i . 'm ago';
                                                } else {
                                                    echo $diff->h . 'h ago';
                                                }
                                            } elseif ($diff->days == 1) {
                                                echo 'Yesterday';
                                            } elseif ($diff->days < 7) {
                                                echo $diff->days . 'd ago';
                                            } else {
                                                echo $createdAt->format('M d');
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Employees in Office
                    </h3>
                </div>
                <div class="card-content">
                    <?php if (empty($employeesInOffice)): ?>
                        <div class="empty-state">
                            <p>No employees currently in office</p>
                        </div>
                    <?php else: ?>
                        <div class="leaves-list-compact">
                            <?php foreach ($employeesInOffice as $employee): ?>
                                <div class="leave-item-compact">
                                    <div class="leave-header-compact">
                                        <?php echo getUserAvatar($employee, 'small'); ?>
                                        <div class="leave-info-compact">
                                            <p class="leave-name-compact"><?php echo htmlspecialchars($employee['full_name']); ?></p>
                                            <?php if ($employee['title']): ?>
                                                <p class="leave-title-compact"><?php echo htmlspecialchars($employee['title']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="leave-details-compact">
                                        <?php
                                        $checkInTime = new DateTime($employee['check_in'], new DateTimeZone('Asia/Kolkata'));
                                        ?>
                                        <p class="leave-dates-compact">
                                            Checked in: <?php echo $checkInTime->format('g:i A'); ?>
                                        </p>
                                        <span class="badge badge-green">In Office</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Employees on Break
                    </h3>
                </div>
                <div class="card-content">
                    <?php if (empty($employeesOnBreak)): ?>
                        <div class="empty-state">
                            <p>No employees currently on break</p>
                        </div>
                    <?php else: ?>
                        <div class="leaves-list-compact">
                            <?php foreach ($employeesOnBreak as $employee): ?>
                                <div class="leave-item-compact">
                                    <div class="leave-header-compact">
                                        <?php echo getUserAvatar($employee, 'small'); ?>
                                        <div class="leave-info-compact">
                                            <p class="leave-name-compact"><?php echo htmlspecialchars($employee['full_name']); ?></p>
                                            <?php if ($employee['title']): ?>
                                                <p class="leave-title-compact"><?php echo htmlspecialchars($employee['title']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="leave-details-compact">
                                        <?php
                                        $breakStart = new DateTime($employee['break_start'], new DateTimeZone('Asia/Kolkata'));
                                        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                                        $duration = $now->diff($breakStart);
                                        $minutes = ($duration->h * 60) + $duration->i;
                                        ?>
                                        <p class="leave-dates-compact">
                                            Since: <?php echo $breakStart->format('g:i A'); ?> (<?php echo $minutes; ?>m)
                                        </p>
                                        <span class="badge badge-orange">
                                            <?php echo ucfirst($employee['break_type']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Team Members on Leave
                    </h3>
                </div>
                <div class="card-content">
                    <?php if (empty($usersOnLeave)): ?>
                        <div class="empty-state">
                            <p>No team members on leave today</p>
                        </div>
                    <?php else: ?>
                        <div class="leaves-list-compact">
                            <?php foreach ($usersOnLeave as $leave): ?>
                                <div class="leave-item-compact">
                                    <div class="leave-header-compact">
                                        <?php echo getUserAvatar($leave, 'small'); ?>
                                        <div class="leave-info-compact">
                                            <p class="leave-name-compact"><?php echo htmlspecialchars($leave['full_name']); ?></p>
                                            <?php if ($leave['title']): ?>
                                                <p class="leave-title-compact"><?php echo htmlspecialchars($leave['title']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="leave-details-compact">
                                        <p class="leave-dates-compact">
                                            <?php echo date('M d', strtotime($leave['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                        </p>
                                        <span class="badge badge-blue">
                                            <?php echo ucfirst($leave['type']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.notifications-list-compact {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification-item-compact {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid;
    background: var(--light);
    transition: all 0.2s;
    cursor: pointer;
}

.notification-item-compact:hover {
    background: var(--bg);
    box-shadow: var(--shadow);
}

.notification-item-compact.notification-info {
    border-left-color: #3b82f6;
}

.notification-item-compact.notification-warning {
    border-left-color: #f59e0b;
}

.notification-item-compact.notification-success {
    border-left-color: #10b981;
}

.notification-item-compact.notification-important {
    border-left-color: #ef4444;
}

.notification-icon-compact {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.notification-item-compact.notification-info .notification-icon-compact {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.notification-item-compact.notification-warning .notification-icon-compact {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.notification-item-compact.notification-success .notification-icon-compact {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.notification-item-compact.notification-important .notification-icon-compact {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.notification-icon-compact svg {
    width: 18px;
    height: 18px;
}

.notification-content-compact {
    flex: 1;
    min-width: 0;
}

.notification-title-compact {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin: 0 0 4px 0;
}

.notification-message-compact {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0 0 4px 0;
    line-height: 1.4;
}

.notification-date-compact {
    font-size: 11px;
    color: var(--text-muted);
}

[data-theme="dark"] .notification-item-compact {
    background: rgba(255, 255, 255, 0.03);
}

[data-theme="dark"] .notification-item-compact:hover {
    background: rgba(255, 255, 255, 0.05);
}

.leaves-list-compact {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.leave-item-compact {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--light);
    transition: all 0.2s;
}

.leave-item-compact:hover {
    background: var(--bg);
    box-shadow: var(--shadow);
    border-color: var(--primary);
}

[data-theme="dark"] .leave-item-compact {
    background: rgba(255, 255, 255, 0.03);
}

[data-theme="dark"] .leave-item-compact:hover {
    background: rgba(255, 255, 255, 0.05);
}

.leave-header-compact {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.leave-info-compact {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.leave-name-compact {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
    margin: 0;
}

.leave-title-compact {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
}

.leave-details-compact {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.leave-dates-compact {
    font-size: 13px;
    color: var(--text-muted);
    margin: 0;
    white-space: nowrap;
}

/* Enhanced Dashboard Leave Styles */
.leaves-list-compact {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.leave-item-compact {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 20px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: linear-gradient(135deg, var(--card-bg) 0%, rgba(59, 130, 246, 0.02) 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.leave-item-compact::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #3b82f6 0%, #8b5cf6 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.leave-item-compact:hover {
    background: linear-gradient(135deg, var(--bg) 0%, rgba(59, 130, 246, 0.05) 100%);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.15);
    border-color: #3b82f6;
    transform: translateX(4px);
}

.leave-item-compact:hover::before {
    opacity: 1;
}

[data-theme="dark"] .leave-item-compact {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(59, 130, 246, 0.05) 100%);
}

[data-theme="dark"] .leave-item-compact:hover {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(59, 130, 246, 0.08) 100%);
}

.leave-header-compact {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}

.leave-info-compact {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.leave-name-compact {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.leave-title-compact {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.leave-details-compact {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    margin-left: 16px;
}

.leave-dates-compact {
    font-size: 13px;
    color: var(--text);
    margin: 0;
    white-space: nowrap;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}



.dashboard-card .card-header {
    border-bottom: 2px solid var(--border);
    padding-bottom: 16px;
    margin-bottom: 20px;
}

.dashboard-card .card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
}

.dashboard-card .card-title svg {
    width: 22px;
    height: 22px;
    color: #3b82f6;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
