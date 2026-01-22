<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';

$auth = new Auth();
$auth->requireAuth();
$storage = new Storage();

$user = $auth->getUser();
$userId = $user['id'];

$attendanceHistory = $storage->getAttendance($userId);
$today = date('Y-m-d');
$todaysRecord = null;

foreach ($attendanceHistory as $att) {
    if ($att['date'] === $today) {
        $todaysRecord = $att;
        break;
    }
}

$isCheckedIn = $todaysRecord && $todaysRecord['check_in'];
$isCheckedOut = $todaysRecord && $todaysRecord['check_out'];
$activeBreak = null;
if ($todaysRecord) {
    $activeBreak = $storage->getActiveBreak($userId, $todaysRecord['id']);
}
$todaysBreaks = [];
if ($todaysRecord) {
    $todaysBreaks = $storage->getBreaksByAttendance($todaysRecord['id']);
}

function convertToIST($datetime) {
    if (!$datetime) return null;
    try {
        $istTime = new DateTime($datetime, new DateTimeZone('Asia/Kolkata'));
        return $istTime;
    } catch (Exception $e) {
        return new DateTime($datetime);
    }
}

function getStatusColor($status) {
    switch($status) {
        case 'present': return 'badge-green';
        case 'late': return 'badge-orange';
        case 'absent': return 'badge-red';
        case 'out': return 'badge-gray';
        default: return 'badge-gray';
    }
}

$pageTitle = 'Cloveode Attendance System';
$showSidebar = true;
include __DIR__ . '/includes/header.php';
?>
<main class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Here is Your Attendance Record</h1>
                <p class="page-subtitle">Manage check-ins and view history</p>
            </div>
        </div>

        <div class="card action-card">
            <div class="card-content">
                <div class="action-header">
                    <div class="action-info">
                        <div class="action-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="action-title"><?php echo date('l, F jS'); ?></h2>
                            <p class="action-subtitle">
                                <?php 
                                if ($isCheckedIn) {
                                    $checkInTime = convertToIST($todaysRecord['check_in']);
                                    echo 'Checked in at ' . $checkInTime->format('g:i A');
                                } else {
                                    echo "You haven't checked in yet today";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button 
                            class="btn btn-success btn-lg" 
                            id="checkInBtn"
                            <?php echo $isCheckedIn ? 'disabled' : ''; ?>
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            Check In
                        </button>
                        <button 
                            class="btn btn-outline btn-lg" 
                            id="checkOutBtn"
                            <?php echo (!$isCheckedIn || $isCheckedOut) ? 'disabled' : ''; ?>
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg>
                            Check Out
                        </button>
                    </div>
                </div>
                <?php if ($isCheckedIn && !$isCheckedOut): ?>
                    <div class="break-section" style="margin-top: 24px; padding-top: 24px; border-top: 2px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div>
                                <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: var(--text);">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Break / Outside Time
                                </h3>
                                <?php if ($activeBreak): ?>
                                    <?php
                                    $breakStart = new DateTime($activeBreak['break_start'], new DateTimeZone('Asia/Kolkata'));
                                    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                                    $duration = $now->diff($breakStart);
                                    $minutes = ($duration->h * 60) + $duration->i;
                                    ?>
                                    <p style="margin: 0; color: var(--text-muted); font-size: 14px;">
                                        Currently on <?php echo $activeBreak['break_type'] === 'break' ? 'break' : 'outside'; ?> 
                                        since <?php echo $breakStart->format('g:i A'); ?> (<?php echo $minutes; ?> minutes)
                                    </p>
                                <?php else: ?>
                                    <p style="margin: 0; color: var(--text-muted); font-size: 14px;">
                                        Take a break or go outside for a while
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="break-buttons" style="display: flex; gap: 12px;">
                                <?php if ($activeBreak): ?>
                                    <button class="btn btn-success" id="endBreakBtn">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        End Break
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline" id="startBreakBtn" onclick="openBreakModal()">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Start Break
                                    </button>
                                    <button class="btn btn-outline" id="startOutsideBtn" onclick="openBreakModal('outside')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Go Outside
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($todaysBreaks)): ?>
                            <div class="breaks-list" style="margin-top: 16px;">
                                <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: var(--text);">Today's Breaks:</h4>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ($todaysBreaks as $break): ?>
                                        <div style="padding: 12px; background: var(--light); border-radius: 8px; border: 1px solid var(--border);">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <span style="font-weight: 600; color: var(--text);">
                                                        <?php echo ucfirst($break['break_type']); ?>
                                                    </span>
                                                    <?php if ($break['reason']): ?>
                                                        <span style="color: var(--text-muted); margin-left: 8px;">- <?php echo htmlspecialchars($break['reason']); ?></span>
                                                    <?php endif; ?>
                                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                                        <?php
                                                        $start = new DateTime($break['break_start'], new DateTimeZone('Asia/Kolkata'));
                                                        echo 'Started: ' . $start->format('g:i A');
                                                        if ($break['break_end']):
                                                            $end = new DateTime($break['break_end'], new DateTimeZone('Asia/Kolkata'));
                                                            $duration = $end->diff($start);
                                                            $totalMinutes = ($duration->h * 60) + $duration->i;
                                                            echo ' | Ended: ' . $end->format('g:i A') . ' (' . $totalMinutes . ' min)';
                                                        else:
                                                            echo ' (Active)';
                                                        endif;
                                                        ?>
                                                    </div>
                                                </div>
                                                <?php if ($break['break_end']): ?>
                                                    <span class="badge badge-green">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-orange">Active</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Attendance History
                </h3>
            </div>
            <div class="card-content">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <?php if (empty($attendanceHistory)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceHistory as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($record['check_in']) {
                                            $checkInTime = convertToIST($record['check_in']);
                                            echo $checkInTime->format('g:i A');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($record['check_out']) {
                                            $checkOutTime = convertToIST($record['check_out']);
                                            echo $checkOutTime->format('g:i A');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $displayStatus = $record['check_out'] ? 'out' : $record['status'];
                                        ?>
                                        <span class="badge <?php echo getStatusColor($displayStatus); ?>"><?php echo strtoupper($displayStatus); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div id="breakModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Start Break / Go Outside</h2>
            <button class="modal-close" onclick="closeBreakModal()">&times;</button>
        </div>
        <form id="breakForm" onsubmit="startBreak(event)">
            <div class="modal-body">
                <input type="hidden" id="breakType" name="break_type" value="break">
                <div class="form-group">
                    <label for="breakReason">Reason (Optional)</label>
                    <input type="text" id="breakReason" name="reason" class="form-control" placeholder="e.g., Lunch break, Meeting outside office">
                </div>
                <div class="form-group">
                    <label for="expectedDuration">Expected Duration (Optional)</label>
                    <select id="expectedDuration" name="expected_duration_minutes" class="form-control">
                        <option value="">Select duration</option>
                        <option value="15">15 minutes</option>
                        <option value="20">20 minutes</option>
                        <option value="30">30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeBreakModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Start</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 2px solid var(--border);
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: var(--light);
    color: var(--text);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 24px;
    border-top: 2px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text);
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    background: var(--bg);
    color: var(--text);
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>

<script>
function openBreakModal(type = 'break') {
    const modal = document.getElementById('breakModal');
    const breakTypeInput = document.getElementById('breakType');
    const modalTitle = document.querySelector('#breakModal .modal-header h2');
    
    breakTypeInput.value = type;
    if (type === 'outside') {
        modalTitle.textContent = 'Go Outside';
    } else {
        modalTitle.textContent = 'Start Break';
    }
    
    if (modal) {
        modal.classList.add('active');
    }
}

function closeBreakModal() {
    const modal = document.getElementById('breakModal');
    if (modal) {
        modal.classList.remove('active');
        document.getElementById('breakForm').reset();
    }
}

async function startBreak(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const data = {
        break_type: formData.get('break_type'),
        reason: formData.get('reason') || null,
        expected_duration_minutes: formData.get('expected_duration_minutes') ? parseInt(formData.get('expected_duration_minutes')) : null
    };
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Starting...';
    
    try {
        const formDataToSend = new FormData();
        formDataToSend.append('break_type', data.break_type);
        if (data.reason) formDataToSend.append('reason', data.reason);
        if (data.expected_duration_minutes) formDataToSend.append('expected_duration_minutes', data.expected_duration_minutes);
        
        const response = await fetch(BASE_URL + '/api/breaks.php?action=start', {
            method: 'POST',
            body: formDataToSend
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Error starting break');
        }
        
        closeBreakModal();
        location.reload();
    } catch (error) {
        alert(error.message || 'Error starting break. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function endBreak() {
    const btn = document.getElementById('endBreakBtn');
    if (!btn) return;
    
    btn.disabled = true;
    btn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Ending...';
    
    try {
        const response = await fetch(BASE_URL + '/api/breaks.php?action=end', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || 'Error ending break');
        }
        
        location.reload();
    } catch (error) {
        alert(error.message || 'Error ending break. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg> End Break';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const endBreakBtn = document.getElementById('endBreakBtn');
    if (endBreakBtn) {
        endBreakBtn.addEventListener('click', endBreak);
    }
    
    const breakModal = document.getElementById('breakModal');
    if (breakModal) {
        breakModal.addEventListener('click', function(e) {
            if (e.target === breakModal) {
                closeBreakModal();
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
