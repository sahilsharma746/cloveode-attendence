<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/helpers.php';

$auth = new Auth();
$auth->requireAdmin();
$storage = new Storage();

$sessionUser = $auth->getUser();
$user = $storage->getUser($sessionUser['id']);
$auth->updateSessionUser($user);
$users = $storage->getAllUsers();
$leaves = $storage->getLeaves();
$pendingLeaves = array_filter($leaves, fn($l) => $l['status'] === 'pending');
$approvedLeaves = array_filter($leaves, fn($l) => $l['status'] === 'approved');
$rejectedLeaves = array_filter($leaves, fn($l) => $l['status'] === 'rejected');
$holidays = $storage->getHolidays();
$notifications = $storage->getNotifications(null, false);

$selectedUserId = isset($_GET['attendance_user']) ? (int)$_GET['attendance_user'] : null;
$selectedMonth = isset($_GET['attendance_month']) ? (int)$_GET['attendance_month'] : date('n');
$selectedYear = isset($_GET['attendance_year']) ? (int)$_GET['attendance_year'] : date('Y');

$attendanceRecords = $storage->getAttendanceWithUsers($selectedUserId, $selectedMonth, $selectedYear);
$attendanceStats = $storage->getAllUsersAttendanceStats($selectedMonth, $selectedYear);
$allBreaks = $storage->getAllBreaksWithUsers($selectedUserId, $selectedMonth, $selectedYear);

$breaksByAttendance = [];
foreach ($allBreaks as $break) {
    $attendanceId = $break['attendance_id'];
    if (!isset($breaksByAttendance[$attendanceId])) {
        $breaksByAttendance[$attendanceId] = [];
    }
    $breaksByAttendance[$attendanceId][] = $break;
}

$pageTitle = 'Cloveode Attendance System';
$showSidebar = true;
include __DIR__ . '/includes/header.php';
?>
<main class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Admin Panel</h1>
                <p class="page-subtitle">Manage employees and approve requests</p>
            </div>
        </div>

        <div class="tabs">
            <div class="tabs-header">
                <?php 
                $activeTab = 'leaves';
                if (isset($_GET['attendance_month']) || isset($_GET['attendance_user'])) {
                    $activeTab = 'attendance';
                }
                if (isset($_GET['tab']) && $_GET['tab'] === 'holidays') {
                    $activeTab = 'holidays';
                }
                if (isset($_GET['tab']) && $_GET['tab'] === 'employees') {
                    $activeTab = 'employees';
                }
                if (isset($_GET['tab']) && $_GET['tab'] === 'notifications') {
                    $activeTab = 'notifications';
                }
                ?>
                <button class="tab-btn <?php echo $activeTab === 'leaves' ? 'active' : ''; ?>" onclick="switchTab('leaves')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Leave Requests
                    <?php if (count($pendingLeaves) > 0): ?>
                        <span class="badge badge-red"><?php echo count($pendingLeaves); ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn <?php echo $activeTab === 'attendance' ? 'active' : ''; ?>" onclick="switchTab('attendance')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Attendance
                </button>
                <button class="tab-btn <?php echo $activeTab === 'employees' ? 'active' : ''; ?>" onclick="switchTab('employees')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Employees
                </button>
                <button class="tab-btn <?php echo $activeTab === 'holidays' ? 'active' : ''; ?>" onclick="switchTab('holidays')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Holidays
                </button>
                <button class="tab-btn <?php echo $activeTab === 'notifications' ? 'active' : ''; ?>" onclick="switchTab('notifications')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Notifications
                </button>
            </div>

            <div id="tabLeaves" class="tab-content <?php echo $activeTab === 'leaves' ? 'active' : ''; ?>">
                <div class="leave-section-card leave-section-pending">
                    <div class="leave-section-header">
                        <div class="leave-section-title-wrapper">
                            <div class="leave-section-icon leave-icon-pending">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="leave-section-title">Pending Approvals</h3>
                                <p class="leave-section-subtitle">Requires your attention</p>
                            </div>
                        </div>
                        <?php if (count($pendingLeaves) > 0): ?>
                            <span class="leave-count-badge leave-count-pending"><?php echo count($pendingLeaves); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="leave-section-content">
                        <?php if (empty($pendingLeaves)): ?>
                            <div class="empty-state-modern">
                                <div class="empty-state-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="empty-state-text">All caught up! No pending requests</p>
                            </div>
                        <?php else: ?>
                            <div class="leave-table-wrapper">
                                <table class="leave-table-modern">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Type</th>
                                            <th>Dates</th>
                                            <th>Reason</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingLeaves as $leave): 
                                            $employee = null;
                                            foreach ($users as $u) {
                                                if ($u['id'] == $leave['user_id']) {
                                                    $employee = $u;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <tr class="leave-row-modern">
                                                <td>
                                                    <div class="user-cell-modern">
                                                        <?php echo getUserAvatar($employee ?? ['full_name' => 'Unknown', 'profile_picture' => null], 'small'); ?>
                                                        <div class="user-info-modern">
                                                            <span class="user-name-modern"><?php echo htmlspecialchars($employee['full_name'] ?? 'Unknown'); ?></span>
                                                            <?php if ($employee['title'] ?? null): ?>
                                                                <span class="user-title-modern"><?php echo htmlspecialchars($employee['title']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="leave-type-badge leave-type-<?php echo strtolower($leave['type']); ?>">
                                                        <?php echo ucfirst($leave['type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="leave-dates-modern">
                                                        <span class="date-start"><?php echo date('M d', strtotime($leave['start_date'])); ?></span>
                                                        <span class="date-separator">→</span>
                                                        <span class="date-end"><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="leave-reason-modern" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                                        <?php echo htmlspecialchars($leave['reason']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="action-buttons-modern">
                                                        <button 
                                                            class="btn-action btn-approve" 
                                                            onclick="updateLeaveStatus(<?php echo $leave['id']; ?>, 'approved')"
                                                            title="Approve"
                                                        >
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Approve
                                                        </button>
                                                        <button 
                                                            class="btn-action btn-reject" 
                                                            onclick="updateLeaveStatus(<?php echo $leave['id']; ?>, 'rejected')"
                                                            title="Reject"
                                                        >
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            Reject
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="leave-section-card leave-section-approved">
                    <div class="leave-section-header">
                        <div class="leave-section-title-wrapper">
                            <div class="leave-section-icon leave-icon-approved">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="leave-section-title">Approved Leaves</h3>
                                <p class="leave-section-subtitle">Successfully approved requests</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if (count($approvedLeaves) > 0): ?>
                                <span class="leave-count-badge leave-count-approved"><?php echo count($approvedLeaves); ?></span>
                            <?php endif; ?>
                            <?php if (count($approvedLeaves) > 0): ?>
                                <button 
                                    class="btn-clear-history" 
                                    onclick="clearLeaveHistory('approved')"
                                    title="Clear approved leaves history"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Clear History
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="leave-section-content">
                        <?php if (empty($approvedLeaves)): ?>
                            <div class="empty-state-modern">
                                <div class="empty-state-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="empty-state-text">No approved leaves yet</p>
                            </div>
                        <?php else: ?>
                            <div class="leave-table-wrapper">
                                <table class="leave-table-modern">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Type</th>
                                            <th>Dates</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approvedLeaves as $leave): 
                                            $employee = null;
                                            foreach ($users as $u) {
                                                if ($u['id'] == $leave['user_id']) {
                                                    $employee = $u;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <tr class="leave-row-modern">
                                                <td>
                                                    <div class="user-cell-modern">
                                                        <?php echo getUserAvatar($employee ?? ['full_name' => 'Unknown', 'profile_picture' => null], 'small'); ?>
                                                        <div class="user-info-modern">
                                                            <span class="user-name-modern"><?php echo htmlspecialchars($employee['full_name'] ?? 'Unknown'); ?></span>
                                                            <?php if ($employee['title'] ?? null): ?>
                                                                <span class="user-title-modern"><?php echo htmlspecialchars($employee['title']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="leave-type-badge leave-type-<?php echo strtolower($leave['type']); ?>">
                                                        <?php echo ucfirst($leave['type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="leave-dates-modern">
                                                        <span class="date-start"><?php echo date('M d', strtotime($leave['start_date'])); ?></span>
                                                        <span class="date-separator">→</span>
                                                        <span class="date-end"><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="leave-reason-modern" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                                        <?php echo htmlspecialchars($leave['reason']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge-modern status-approved">
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Approved
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="leave-section-card leave-section-rejected">
                    <div class="leave-section-header">
                        <div class="leave-section-title-wrapper">
                            <div class="leave-section-icon leave-icon-rejected">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </div>
                            <div>
                                <h3 class="leave-section-title">Rejected Leaves</h3>
                                <p class="leave-section-subtitle">Declined leave requests</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if (count($rejectedLeaves) > 0): ?>
                                <span class="leave-count-badge leave-count-rejected"><?php echo count($rejectedLeaves); ?></span>
                            <?php endif; ?>
                            <?php if (count($rejectedLeaves) > 0): ?>
                                <button 
                                    class="btn-clear-history" 
                                    onclick="clearLeaveHistory('rejected')"
                                    title="Clear rejected leaves history"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Clear History
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="leave-section-content">
                        <?php if (empty($rejectedLeaves)): ?>
                            <div class="empty-state-modern">
                                <div class="empty-state-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="empty-state-text">No rejected leaves</p>
                            </div>
                        <?php else: ?>
                            <div class="leave-table-wrapper">
                                <table class="leave-table-modern">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Type</th>
                                            <th>Dates</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rejectedLeaves as $leave): 
                                            $employee = null;
                                            foreach ($users as $u) {
                                                if ($u['id'] == $leave['user_id']) {
                                                    $employee = $u;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <tr class="leave-row-modern">
                                                <td>
                                                    <div class="user-cell-modern">
                                                        <?php echo getUserAvatar($employee ?? ['full_name' => 'Unknown', 'profile_picture' => null], 'small'); ?>
                                                        <div class="user-info-modern">
                                                            <span class="user-name-modern"><?php echo htmlspecialchars($employee['full_name'] ?? 'Unknown'); ?></span>
                                                            <?php if ($employee['title'] ?? null): ?>
                                                                <span class="user-title-modern"><?php echo htmlspecialchars($employee['title']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="leave-type-badge leave-type-<?php echo strtolower($leave['type']); ?>">
                                                        <?php echo ucfirst($leave['type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="leave-dates-modern">
                                                        <span class="date-start"><?php echo date('M d', strtotime($leave['start_date'])); ?></span>
                                                        <span class="date-separator">→</span>
                                                        <span class="date-end"><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="leave-reason-modern" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                                        <?php echo htmlspecialchars($leave['reason']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge-modern status-rejected">
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        Rejected
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="tabAttendance" class="tab-content <?php echo $activeTab === 'attendance' ? 'active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Attendance Records</h3>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <select id="attendanceUserFilter" class="form-control" style="width: auto; min-width: 200px;" onchange="filterAttendance()">
                                <option value="">All Users</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo $selectedUserId == $u['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select id="attendanceMonthFilter" class="form-control" style="width: auto;" onchange="filterAttendance()">
                                <?php
                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                for ($i = 1; $i <= 12; $i++):
                                ?>
                                    <option value="<?php echo $i; ?>" <?php echo $selectedMonth == $i ? 'selected' : ''; ?>>
                                        <?php echo $months[$i - 1]; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select id="attendanceYearFilter" class="form-control" style="width: auto;" onchange="filterAttendance()">
                                <?php
                                $currentYear = date('Y');
                                for ($y = $currentYear; $y >= $currentYear - 2; $y--):
                                ?>
                                    <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-content">
                        <?php if ($selectedUserId): 
                            $userStats = $attendanceStats[$selectedUserId] ?? null;
                            if ($userStats):
                        ?>
                            <div class="stats-grid" style="margin-bottom: 2rem;">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $userStats['present_days']; ?></div>
                                    <div class="stat-label">Days Present</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $userStats['leave_days']; ?></div>
                                    <div class="stat-label">Days on Leave</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $userStats['total_working_days']; ?></div>
                                    <div class="stat-label">Total Working Days</div>
                                </div>
                            </div>
                        <?php endif; endif; ?>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Check In (IST)</th>
                                    <th>Check Out (IST)</th>
                                    <th>Status</th>
                                    <th>Late By</th>
                                    <th>Breaks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendanceRecords)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No attendance records found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($attendanceRecords as $record): 
                                        $recordBreaks = $breaksByAttendance[$record['id']] ?? [];
                                        $totalBreakDuration = 0;
                                        $activeBreaks = 0;
                                        foreach ($recordBreaks as $br) {
                                            if ($br['break_end']) {
                                                $start = new DateTime($br['break_start'], new DateTimeZone('Asia/Kolkata'));
                                                $end = new DateTime($br['break_end'], new DateTimeZone('Asia/Kolkata'));
                                                $diff = $start->diff($end);
                                                $totalBreakDuration += ($diff->h * 60) + $diff->i;
                                            } else {
                                                $activeBreaks++;
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    <?php 
                                                    $recordUser = null;
                                                    foreach ($users as $u) {
                                                        if ($u['id'] == $record['user_id']) {
                                                            $recordUser = $u;
                                                            break;
                                                        }
                                                    }
                                                    echo getUserAvatar($recordUser ?? ['full_name' => $record['full_name'], 'profile_picture' => null], 'small');
                                                    ?>
                                                    <div>
                                                        <p class="font-medium"><?php echo htmlspecialchars($record['full_name']); ?></p>
                                                        <?php if ($record['title']): ?>
                                                            <p class="text-xs text-muted"><?php echo htmlspecialchars($record['title']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td class="text-sm text-muted">
                                                <?php 
                                                if ($record['check_in']) {
                                                    $checkInTime = new DateTime($record['check_in'], new DateTimeZone('Asia/Kolkata'));
                                                    echo $checkInTime->format('h:i A');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-sm text-muted">
                                                <?php 
                                                if ($record['check_out']) {
                                                    $checkOutTime = new DateTime($record['check_out'], new DateTimeZone('Asia/Kolkata'));
                                                    echo $checkOutTime->format('h:i A');
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $displayStatus = $record['check_out'] ? 'out' : $record['status'];
                                                
                                                $statusColor = 'badge-green';
                                                if ($displayStatus === 'late') {
                                                    $statusColor = 'badge-red';
                                                } elseif ($displayStatus === 'absent') {
                                                    $statusColor = 'badge-red';
                                                } elseif ($displayStatus === 'out') {
                                                    $statusColor = 'badge-gray';
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusColor; ?> capitalize"><?php echo strtoupper($displayStatus); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($record['status'] === 'late' && isset($record['late_minutes']) && $record['late_minutes']): ?>
                                                    <span class="text-sm" style="color: #ef4444; font-weight: 600;">
                                                        <?php echo $record['late_minutes']; ?> minutes
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-sm text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($recordBreaks)): ?>
                                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                                        <span class="text-sm" style="font-weight: 600; color: var(--text);">
                                                            <?php echo count($recordBreaks); ?> break<?php echo count($recordBreaks) > 1 ? 's' : ''; ?>
                                                        </span>
                                                        <?php if ($totalBreakDuration > 0): ?>
                                                            <span class="text-xs text-muted">
                                                                Total: <?php echo floor($totalBreakDuration / 60); ?>h <?php echo $totalBreakDuration % 60; ?>m
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($activeBreaks > 0): ?>
                                                            <span class="text-xs" style="color: #f59e0b; font-weight: 600;">
                                                                <?php echo $activeBreaks; ?> active
                                                            </span>
                                                        <?php endif; ?>
                                                        <button 
                                                            onclick="toggleBreakDetails(<?php echo $record['id']; ?>)"
                                                            style="margin-top: 4px; padding: 2px 0; text-align: left; background: none; border: none; color: #3b82f6; cursor: pointer; font-size: 12px; text-decoration: underline;"
                                                        >
                                                            View Details
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-sm text-muted">No breaks</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if (!empty($recordBreaks)): ?>
                                            <tr id="breakDetails_<?php echo $record['id']; ?>" style="display: none; background-color: var(--light);">
                                                <td colspan="7" style="padding: 16px;">
                                                    <div style="margin-left: 24px;">
                                                        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: var(--text);">Break Details:</h4>
                                                        <div style="display: flex; flex-direction: column; gap: 12px;">
                                                            <?php foreach ($recordBreaks as $br): 
                                                                $breakStart = new DateTime($br['break_start'], new DateTimeZone('Asia/Kolkata'));
                                                                $isActive = !$br['break_end'];
                                                                $duration = '';
                                                                if ($br['break_end']) {
                                                                    $breakEnd = new DateTime($br['break_end'], new DateTimeZone('Asia/Kolkata'));
                                                                    $diff = $breakStart->diff($breakEnd);
                                                                    $duration = ($diff->h > 0 ? $diff->h . 'h ' : '') . $diff->i . 'm';
                                                                } else {
                                                                    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                                                                    $diff = $breakStart->diff($now);
                                                                    $duration = ($diff->h > 0 ? $diff->h . 'h ' : '') . $diff->i . 'm (ongoing)';
                                                                }
                                                            ?>
                                                                <div style="padding: 12px; background: var(--card-bg); border-radius: 8px; border-left: 3px solid <?php echo $isActive ? '#f59e0b' : '#10b981'; ?>;">
                                                                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 8px;">
                                                                        <div style="flex: 1;">
                                                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                                                                <span class="badge <?php echo $br['break_type'] === 'outside' ? 'badge-orange' : 'badge-blue'; ?>" style="font-size: 11px;">
                                                                                    <?php echo ucfirst($br['break_type']); ?>
                                                                                </span>
                                                                                <?php if ($isActive): ?>
                                                                                    <span class="badge badge-yellow" style="font-size: 11px;">Active</span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <div style="font-size: 13px; color: var(--text); margin-bottom: 4px;">
                                                                                <strong>Start:</strong> <?php echo $breakStart->format('h:i A'); ?>
                                                                                <?php if ($br['break_end']): 
                                                                                    $breakEnd = new DateTime($br['break_end'], new DateTimeZone('Asia/Kolkata'));
                                                                                ?>
                                                                                    | <strong>End:</strong> <?php echo $breakEnd->format('h:i A'); ?>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <?php if ($br['reason']): ?>
                                                                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                                                                    <strong>Reason:</strong> <?php echo htmlspecialchars($br['reason']); ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div style="text-align: right;">
                                                                            <div style="font-size: 13px; font-weight: 600; color: var(--text);">
                                                                                Duration: <?php echo $duration; ?>
                                                                            </div>
                                                                            <?php if ($br['expected_duration_minutes']): ?>
                                                                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                                                    Expected: <?php echo floor($br['expected_duration_minutes'] / 60); ?>h <?php echo $br['expected_duration_minutes'] % 60; ?>m
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php
                $breakStats = [
                    'total_breaks' => count($allBreaks),
                    'active_breaks' => 0,
                    'total_duration' => 0,
                    'break_types' => ['break' => 0, 'outside' => 0]
                ];
                date_default_timezone_set('Asia/Kolkata');
                $today = date('Y-m-d');
                foreach ($allBreaks as $br) {
                    if (!$br['break_end']) {
                        $breakStats['active_breaks']++;
                    } else {
                        $start = new DateTime($br['break_start'], new DateTimeZone('Asia/Kolkata'));
                        $end = new DateTime($br['break_end'], new DateTimeZone('Asia/Kolkata'));
                        $diff = $start->diff($end);
                        $breakStats['total_duration'] += ($diff->h * 60) + $diff->i;
                    }
                    $breakStats['break_types'][$br['break_type']]++;
                }
                ?>
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px; color: #3b82f6;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Break Timing Summary (<?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>)
                        </h3>
                    </div>
                    <div class="card-content">
                        <div class="stats-grid" style="margin-bottom: 1rem;">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $breakStats['total_breaks']; ?></div>
                                <div class="stat-label">Total Breaks</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" style="color: <?php echo $breakStats['active_breaks'] > 0 ? '#f59e0b' : 'inherit'; ?>;">
                                    <?php echo $breakStats['active_breaks']; ?>
                                </div>
                                <div class="stat-label">Active Breaks</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">
                                    <?php echo floor($breakStats['total_duration'] / 60); ?>h <?php echo $breakStats['total_duration'] % 60; ?>m
                                </div>
                                <div class="stat-label">Total Duration</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">
                                    <?php echo $breakStats['break_types']['break']; ?> / <?php echo $breakStats['break_types']['outside']; ?>
                                </div>
                                <div class="stat-label">Break / Outside</div>
                            </div>
                        </div>
                        <?php if (!empty($allBreaks)): ?>
                            <div style="margin-top: 1rem;">
                                <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: var(--text);">All Break Records:</h4>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <table class="data-table" style="font-size: 13px;">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Duration</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allBreaks as $br): 
                                                $breakStart = new DateTime($br['break_start'], new DateTimeZone('Asia/Kolkata'));
                                                $isActive = !$br['break_end'];
                                                $duration = '-';
                                                if ($br['break_end']) {
                                                    $breakEnd = new DateTime($br['break_end'], new DateTimeZone('Asia/Kolkata'));
                                                    $diff = $breakStart->diff($breakEnd);
                                                    $duration = ($diff->h > 0 ? $diff->h . 'h ' : '') . $diff->i . 'm';
                                                } else {
                                                    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                                                    $diff = $breakStart->diff($now);
                                                    $duration = ($diff->h > 0 ? $diff->h . 'h ' : '') . $diff->i . 'm (ongoing)';
                                                }
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($br['full_name']); ?></div>
                                                        <?php if ($br['title']): ?>
                                                            <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($br['title']); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($br['attendance_date'])); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $br['break_type'] === 'outside' ? 'badge-orange' : 'badge-blue'; ?>" style="font-size: 11px;">
                                                            <?php echo ucfirst($br['break_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $breakStart->format('h:i A'); ?></td>
                                                    <td>
                                                        <?php if ($br['break_end']): 
                                                            $breakEnd = new DateTime($br['break_end'], new DateTimeZone('Asia/Kolkata'));
                                                            echo $breakEnd->format('h:i A');
                                                        else: ?>
                                                            <span style="color: #f59e0b; font-weight: 600;">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $duration; ?></td>
                                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($br['reason'] ?? '-'); ?>">
                                                        <?php echo htmlspecialchars($br['reason'] ?? '-'); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($isActive): ?>
                                                            <span class="badge badge-yellow" style="font-size: 11px;">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-green" style="font-size: 11px;">Completed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No break records found for this period</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px; color: #ef4444;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Late Arrivals Today (IST)
                        </h3>
                    </div>
                    <div class="card-content">
                        <?php
                        date_default_timezone_set('Asia/Kolkata');
                        $today = date('Y-m-d');
                        $todayLateArrivals = array_filter($attendanceRecords, function($record) use ($today) {
                            return $record['date'] === $today && $record['status'] === 'late';
                        });
                        ?>
                        <?php if (empty($todayLateArrivals)): ?>
                            <div class="empty-state">
                                <p>No late arrivals today</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Check In Time (IST)</th>
                                        <th>Late By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayLateArrivals as $record): ?>
                                        <tr>
                                            <td>
                                                <div class="user-cell">
                                                    <?php 
                                                    $recordUser = null;
                                                    foreach ($users as $u) {
                                                        if ($u['id'] == $record['user_id']) {
                                                            $recordUser = $u;
                                                            break;
                                                        }
                                                    }
                                                    echo getUserAvatar($recordUser ?? ['full_name' => $record['full_name'], 'profile_picture' => null], 'small');
                                                    ?>
                                                    <div>
                                                        <p class="font-medium"><?php echo htmlspecialchars($record['full_name']); ?></p>
                                                        <?php if ($record['title']): ?>
                                                            <p class="text-xs text-muted"><?php echo htmlspecialchars($record['title']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="color: #ef4444; font-weight: 600;">
                                                    <?php 
                                                    if ($record['check_in']) {
                                                        $checkInTime = new DateTime($record['check_in'], new DateTimeZone('Asia/Kolkata'));
                                                        echo $checkInTime->format('h:i A');
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="color: #ef4444; font-weight: 600;">
                                                    <?php echo isset($record['late_minutes']) ? $record['late_minutes'] : 0; ?> minutes
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">Attendance Summary (<?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>)</h3>
                    </div>
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Days Present</th>
                                    <th>Days on Leave</th>
                                    <th>Total Working Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceStats as $stat): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <?php echo getUserAvatar($stat, 'small'); ?>
                                                <div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($stat['full_name']); ?></p>
                                                    <?php if ($stat['title']): ?>
                                                        <p class="text-xs text-muted"><?php echo htmlspecialchars($stat['title']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $stat['present_days']; ?></td>
                                        <td><?php echo $stat['leave_days']; ?></td>
                                        <td><strong><?php echo $stat['total_working_days']; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tabEmployees" class="tab-content <?php echo $activeTab === 'employees' ? 'active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Employee Directory</h3>
                        <button class="btn btn-primary" onclick="openCreateUserModal()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Create User
                        </button>
                    </div>
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Leave Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <?php echo getUserAvatar($u, 'small'); ?>
                                                <div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($u['full_name']); ?></p>
                                                    <p class="text-xs text-muted"><?php echo htmlspecialchars($u['username']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-outline capitalize"><?php echo $u['role']; ?></span></td>
                                        <td><?php echo $u['leave_balance']; ?> days</td>
                                        <td>
                                            <span class="badge badge-green">
                                                <span class="badge-dot"></span>
                                                Active
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tabHolidays" class="tab-content <?php echo $activeTab === 'holidays' ? 'active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Holidays List</h3>
                        <button class="btn btn-primary" onclick="openAddHolidayModal()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Holiday
                        </button>
                    </div>
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Holiday Name</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($holidays)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No holidays added yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($holidays as $holiday): ?>
                                        <tr>
                                            <td>
                                                <div class="font-medium"><?php echo htmlspecialchars($holiday['name']); ?></div>
                                            </td>
                                            <td>
                                                <div class="text-sm">
                                                    <?php echo date('M d, Y', strtotime($holiday['date'])); ?>
                                                    <span class="text-muted">(<?php echo date('l', strtotime($holiday['date'])); ?>)</span>
                                                </div>
                                            </td>
                                            <td class="max-w-xs truncate" title="<?php echo htmlspecialchars($holiday['description'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($holiday['description'] ?? '-'); ?>
                                            </td>
                                            <td class="text-right">
                                                <button 
                                                    class="btn-icon btn-danger" 
                                                    onclick="deleteHoliday(<?php echo $holiday['id']; ?>, '<?php echo htmlspecialchars($holiday['name'], ENT_QUOTES); ?>')"
                                                    title="Delete"
                                                >
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tabNotifications" class="tab-content <?php echo $activeTab === 'notifications' ? 'active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">System Notifications</h3>
                        <button class="btn btn-primary" onclick="openAddNotificationModal()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Notification
                        </button>
                    </div>
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notifications)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No notifications added yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <tr>
                                            <td>
                                                <div class="font-medium"><?php echo htmlspecialchars($notification['title']); ?></div>
                                            </td>
                                            <td class="max-w-xs truncate" title="<?php echo htmlspecialchars($notification['message']); ?>">
                                                <?php echo htmlspecialchars(substr($notification['message'], 0, 50)) . (strlen($notification['message']) > 50 ? '...' : ''); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $typeColors = [
                                                    'info' => 'badge-blue',
                                                    'warning' => 'badge-orange',
                                                    'success' => 'badge-green',
                                                    'important' => 'badge-red'
                                                ];
                                                $typeColor = $typeColors[$notification['type']] ?? 'badge-outline';
                                                ?>
                                                <span class="badge <?php echo $typeColor; ?> capitalize"><?php echo $notification['type']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($notification['is_active']): ?>
                                                    <span class="badge badge-green">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-outline">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-sm text-muted">
                                                <?php echo htmlspecialchars($notification['created_by_name'] ?? 'Unknown'); ?>
                                            </td>
                                            <td class="text-sm text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                            </td>
                                            <td class="text-right">
                                                <div class="action-buttons-inline">
                                                    <button 
                                                        class="btn-icon btn-success" 
                                                        onclick="toggleNotificationStatus(<?php echo $notification['id']; ?>, <?php echo $notification['is_active'] ? 0 : 1; ?>)"
                                                        title="<?php echo $notification['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                                    >
                                                        <?php if ($notification['is_active']): ?>
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                        <?php else: ?>
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        <?php endif; ?>
                                                    </button>
                                                    <button 
                                                        class="btn-icon btn-danger" 
                                                        onclick="deleteNotification(<?php echo $notification['id']; ?>, '<?php echo htmlspecialchars($notification['title'], ENT_QUOTES); ?>')"
                                                        title="Delete"
                                                    >
                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="addHolidayModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Holiday</h2>
            <button class="modal-close" onclick="closeAddHolidayModal()">&times;</button>
        </div>
        <form id="addHolidayForm" class="modal-form">
            <div class="form-group">
                <label for="holidayName">Holiday Name *</label>
                <input type="text" id="holidayName" name="name" required placeholder="e.g., New Year's Day">
            </div>
            <div class="form-group">
                <label for="holidayDate">Date *</label>
                <input type="date" id="holidayDate" name="date" required>
            </div>
            <div class="form-group">
                <label for="holidayDescription">Description</label>
                <textarea id="holidayDescription" name="description" rows="3" placeholder="Optional description"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddHolidayModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Holiday</button>
            </div>
        </form>
    </div>
</div>

<div id="addNotificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Notification</h2>
            <button class="modal-close" onclick="closeAddNotificationModal()">&times;</button>
        </div>
        <form id="addNotificationForm" class="modal-form">
            <div class="form-group">
                <label for="notificationTitle">Title *</label>
                <input type="text" id="notificationTitle" name="title" required placeholder="e.g., System Maintenance">
            </div>
            <div class="form-group">
                <label for="notificationMessage">Message *</label>
                <textarea id="notificationMessage" name="message" rows="4" required placeholder="Enter notification message"></textarea>
            </div>
            <div class="form-group">
                <label for="notificationType">Type *</label>
                <select id="notificationType" name="type" required>
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="success">Success</option>
                    <option value="important">Important</option>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="notificationActive" name="is_active" value="1" checked>
                    Active (visible to users)
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddNotificationModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Notification</button>
            </div>
        </form>
    </div>
</div>

<div id="createUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create New User</h2>
            <button class="modal-close" onclick="closeCreateUserModal()">&times;</button>
        </div>
        <form id="createUserForm" class="modal-form">
            <div class="form-group">
                <label for="fullName">Full Name *</label>
                <input type="text" id="fullName" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required minlength="3">
                <small class="form-help">At least 3 characters</small>
            </div>
            <div class="form-group">
                <label for="userTitle">Job Title</label>
                <input type="text" id="userTitle" name="title" placeholder="Optional">
            </div>
            <div class="form-group">
                <label for="userRole">Role *</label>
                <select id="userRole" name="role" required>
                    <option value="employee">Employee</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="userPassword">Password *</label>
                <input type="password" id="userPassword" name="password" required minlength="6">
                <small class="form-help">At least 6 characters</small>
            </div>
            <div class="form-group">
                <label for="leaveBalance">Leave Balance</label>
                <input type="number" id="leaveBalance" name="leave_balance" value="20" min="0">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeCreateUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
    
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);
}

function filterAttendance() {
    const userId = document.getElementById('attendanceUserFilter').value;
    const month = document.getElementById('attendanceMonthFilter').value;
    const year = document.getElementById('attendanceYearFilter').value;
    
    const params = new URLSearchParams();
    if (userId) params.append('attendance_user', userId);
    params.append('attendance_month', month);
    params.append('attendance_year', year);
    
    window.location.href = window.location.pathname + '?' + params.toString();
}

function openCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'flex';
    document.getElementById('fullName').focus();
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'none';
    document.getElementById('createUserForm').reset();
}

window.onclick = function(event) {
    const modal = document.getElementById('createUserModal');
    if (event.target == modal) {
        closeCreateUserModal();
    }
}

document.getElementById('createUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        full_name: formData.get('full_name'),
        username: formData.get('username'),
        title: formData.get('title') || '',
        role: formData.get('role'),
        password: formData.get('password'),
        leave_balance: parseInt(formData.get('leave_balance')) || 24
    };
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Creating...';
    
    try {
        const response = await fetch(BASE_URL + '/api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('User created successfully!');
            closeCreateUserModal();
            location.reload(); 
        } else {
            alert(result.message || 'Failed to create user. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

async function updateLeaveStatus(leaveId, status) {
    if (!confirm(`Are you sure you want to ${status} this leave request?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/api/leaves-update.php?id=${leaveId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: status })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            location.reload();
        } else {
            alert(result.message || 'Failed to update leave status');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
}

async function clearLeaveHistory(status) {
    const statusText = status === 'approved' ? 'approved' : 'rejected';
    if (!confirm(`Are you sure you want to clear all ${statusText} leaves history? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/api/leaves-clear.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: status })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert(result.message || `${statusText.charAt(0).toUpperCase() + statusText.slice(1)} leaves history cleared successfully!`);
            location.reload();
        } else {
            alert(result.message || 'Failed to clear history');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
        console.error('Clear history error:', error);
    }
}

function openAddHolidayModal() {
    document.getElementById('addHolidayModal').style.display = 'flex';
    document.getElementById('holidayName').focus();
}

function closeAddHolidayModal() {
    document.getElementById('addHolidayModal').style.display = 'none';
    document.getElementById('addHolidayForm').reset();
}

document.getElementById('addHolidayForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        date: formData.get('date'),
        description: formData.get('description') || ''
    };
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Adding...';
    
    try {
        const response = await fetch(BASE_URL + '/api/holidays.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('Holiday added successfully!');
            closeAddHolidayModal();
            location.reload();
        } else {
            alert(result.message || 'Failed to add holiday. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

async function deleteHoliday(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/api/holidays.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('Holiday deleted successfully!');
            location.reload();
        } else {
            alert(result.message || 'Failed to delete holiday');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
}

function openAddNotificationModal() {
    document.getElementById('addNotificationModal').style.display = 'flex';
    document.getElementById('notificationTitle').focus();
}

function closeAddNotificationModal() {
    document.getElementById('addNotificationModal').style.display = 'none';
    document.getElementById('addNotificationForm').reset();
    document.getElementById('notificationActive').checked = true;
}

document.getElementById('addNotificationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const isActiveCheckbox = document.getElementById('notificationActive');
    
    const data = {
        title: formData.get('title')?.trim() || '',
        message: formData.get('message')?.trim() || '',
        type: formData.get('type') || 'info',
        is_active: isActiveCheckbox.checked ? 1 : 0
    };
    
    if (!data.title || !data.message) {
        alert('Please fill in all required fields (Title and Message)');
        return;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Adding...';
    
    try {
        const response = await fetch(BASE_URL + '/api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('Notification added successfully!');
            closeAddNotificationModal();
            location.reload();
        } else {
            const errorMsg = result.message || 'Failed to add notification. Please try again.';
            alert(errorMsg);
            console.error('Notification API Error:', result);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Notification submission error:', error);
        alert('An error occurred. Please check the console for details or ensure the notifications table exists.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

async function toggleNotificationStatus(id, status) {
    try {
        const response = await fetch(`${BASE_URL}/api/notifications.php?id=${id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ is_active: status })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to update notification status');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
}

async function deleteNotification(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/api/notifications.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('Notification deleted successfully!');
            location.reload();
        } else {
            alert(result.message || 'Failed to delete notification');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
}

function toggleBreakDetails(attendanceId) {
    const detailsRow = document.getElementById('breakDetails_' + attendanceId);
    if (detailsRow) {
        detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
    }
}

window.onclick = function(event) {
    const modals = ['createUserModal', 'addHolidayModal', 'addNotificationModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            if (modalId === 'createUserModal') {
                closeCreateUserModal();
            } else if (modalId === 'addHolidayModal') {
                closeAddHolidayModal();
            } else if (modalId === 'addNotificationModal') {
                closeAddNotificationModal();
            }
        }
    });
}
</script>

<style>
.leave-section-card {
    background: var(--card-bg);
    border-radius: 20px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.leave-section-card:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.leave-section-pending {
    border-left: 4px solid #f59e0b;
}

.leave-section-approved {
    border-left: 4px solid #10b981;
}

.leave-section-rejected {
    border-left: 4px solid #ef4444;
}

.leave-section-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.02) 0%, rgba(0, 0, 0, 0.01) 100%);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

[data-theme="dark"] .leave-section-header {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%);
}

.leave-section-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.leave-section-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.leave-section-icon svg {
    width: 20px;
    height: 20px;
}

.leave-icon-pending {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}

.leave-icon-approved {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.leave-icon-rejected {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.leave-section-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 2px 0;
}

.leave-section-subtitle {
    font-size: 13px;
    color: var(--text-muted);
    margin: 0;
}

.leave-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 12px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 700;
    color: white;
}

.leave-count-pending {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.leave-count-approved {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.leave-count-rejected {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.leave-section-content {
    padding: 16px 20px;
}

.leave-table-wrapper {
    overflow-x: auto;
}

.leave-table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.leave-table-modern thead {
    background: transparent;
}

.leave-table-modern th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
}

.leave-row-modern {
    transition: all 0.2s ease;
    border-bottom: 1px solid var(--border);
}

.leave-row-modern:hover {
    background: rgba(59, 130, 246, 0.03);
    transform: scale(1.001);
}

[data-theme="dark"] .leave-row-modern:hover {
    background: rgba(59, 130, 246, 0.08);
}

.leave-row-modern td {
    padding: 20px;
    vertical-align: middle;
}

.user-cell-modern {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-info-modern {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-name-modern {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
}

.user-title-modern {
    font-size: 12px;
    color: var(--text-muted);
}

.leave-type-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}

.leave-type-casual {
    background: #dbeafe;
    color: #1e40af;
}

.leave-type-sick {
    background: #fee2e2;
    color: #991b1b;
}

.leave-type-annual {
    background: #d1fae5;
    color: #065f46;
}

.leave-dates-modern {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.date-start, .date-end {
    color: var(--text);
    font-weight: 500;
}

.date-separator {
    color: var(--text-muted);
    font-weight: 600;
}

.leave-reason-modern {
    max-width: 400px;
    font-size: 14px;
    color: var(--text);
    line-height: 1.5;
    word-wrap: break-word;
    white-space: normal;
}

.status-badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge-modern svg {
    width: 16px;
    height: 16px;
}

.status-approved {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.status-rejected {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.badge-blue {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: none;
}

.action-buttons-modern {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-action svg {
    width: 16px;
    height: 16px;
}

.btn-approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-approve:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    transform: translateY(-1px);
}

.btn-reject {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-reject:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    transform: translateY(-1px);
}

.btn-clear-history {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid var(--border);
    background: var(--card-bg);
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-clear-history:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: #ef4444;
    transform: translateY(-1px);
}

[data-theme="dark"] .btn-clear-history {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .btn-clear-history:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: #ef4444;
}

.empty-state-modern {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px 24px;
    text-align: center;
}

.empty-state-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    color: var(--text-muted);
}

.empty-state-icon svg {
    width: 20px;
    height: 20px;
}

.empty-state-text {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0;
}

@media (max-width: 768px) {
    .leave-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .action-buttons-modern {
        flex-direction: column;
    }
    
    .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
