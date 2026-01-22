<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';

$auth = new Auth();
$auth->requireAuth();
$storage = new Storage();

$sessionUser = $auth->getUser();
$userId = $sessionUser['id'];
// Fetch fresh user data from database to ensure leave_balance is current
$user = $storage->getUser($userId);
// Update session with fresh data
$auth->updateSessionUser($user);

$leaves = $storage->getLeaves($userId);
$approvedLeaves = count(array_filter($leaves, fn($l) => $l['status'] === 'approved'));
$pendingLeaves = count(array_filter($leaves, fn($l) => $l['status'] === 'pending'));

function getStatusBadge($status) {
    switch($status) {
        case 'approved': 
            return '<span class="badge badge-green"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Approved</span>';
        case 'rejected': 
            return '<span class="badge badge-red"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Rejected</span>';
        default: 
            return '<span class="badge badge-yellow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Pending</span>';
    }
}

$pageTitle = 'Leave Requests';
$showSidebar = true;
include __DIR__ . '/includes/header.php';
?>
<main class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Leave Requests</h1>
                <p class="page-subtitle">Track and manage your time off</p>
            </div>
            <button class="btn btn-primary" onclick="openLeaveModal()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Request
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-blue">
                <div class="stat-content">
                    <p class="stat-label">Total Balance</p>
                    <p class="stat-value"><?php echo $user['leave_balance']; ?></p>
                    <p class="stat-trend">Days remaining</p>
                </div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-content">
                    <p class="stat-label">Used This Year</p>
                    <p class="stat-value"><?php echo $approvedLeaves; ?></p>
                    <p class="stat-trend">Days taken</p>
                </div>
            </div>
            <div class="stat-card stat-orange">
                <div class="stat-content">
                    <p class="stat-label">Pending</p>
                    <p class="stat-value"><?php echo $pendingLeaves; ?></p>
                    <p class="stat-trend">Requests awaiting approval</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Request History
                </h3>
            </div>
            <div class="card-content">
                <div class="leaves-list">
                    <?php if (empty($leaves)): ?>
                        <div class="empty-state">
                            <p>No leave requests found</p>
                            <p class="text-muted">Create a new request to get started</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($leaves as $leave): ?>
                            <div class="leave-item">
                                <div class="leave-info">
                                    <div class="leave-header">
                                        <span class="leave-type"><?php echo ucfirst($leave['type']); ?> Leave</span>
                                        <?php echo getStatusBadge($leave['status']); ?>
                                    </div>
                                    <p class="leave-reason">Reason: <?php echo htmlspecialchars($leave['reason']); ?></p>
                                    <p class="leave-dates">
                                        <?php echo date('M d, Y', strtotime($leave['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="leaveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Apply for Leave</h2>
            <button class="modal-close" onclick="closeLeaveModal()">&times;</button>
        </div>
        <form id="leaveForm" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="startDate">Start Date</label>
                    <input type="date" id="startDate" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="endDate">End Date</label>
                    <input type="date" id="endDate" name="end_date" required>
                </div>
            </div>
            <div class="form-group">
                <label for="leaveType">Leave Type</label>
                <select id="leaveType" name="type" required>
                    <option value="casual">Casual Leave</option>
                    <option value="sick">Sick Leave</option>
                    <option value="emergency">Emergency Leave</option>
                    <option value="other">Half Day Leave</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reason">Reason</label>
                <textarea id="reason" name="reason" rows="4" placeholder="Please briefly explain why..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeLeaveModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
