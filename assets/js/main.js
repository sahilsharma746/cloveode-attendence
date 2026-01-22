const BASE_URL = '/cloveode-attendence';
async function apiCall(endpoint, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    const config = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...(options.headers || {}),
        },
    };

    try {
        const response = await fetch(BASE_URL + endpoint, config);
        
        const text = await response.text();
        let data;
        
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            throw new Error('Server returned an invalid response. Please check your connection and try again.');
        }
        
        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }
        
        return data;
    } catch (error) {
        throw error;
    }
}

async function logout() {
    try {
        await apiCall('/api/logout.php', {
            method: 'POST',
        });
        window.location.href = BASE_URL + '/login.php';
    } catch (error) {
        alert('Error logging out. Please try again.');
    }
}

async function checkIn() {
    const btn = document.getElementById('checkInBtn');
    if (!btn) return;
    
    btn.disabled = true;
    btn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Processing...';
    
    try {
        const data = await apiCall('/api/attendance.php?action=check-in', {
            method: 'POST',
        });
        location.reload();
    } catch (error) {
        alert(error.message || 'Error checking in. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg> Check In';
    }
}

async function checkOut() {
    const btn = document.getElementById('checkOutBtn');
    if (!btn) return;
    
    btn.disabled = true;
    btn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Processing...';
    
    try {
        const data = await apiCall('/api/attendance.php?action=check-out', {
            method: 'POST',
        });
        location.reload();
    } catch (error) {
        alert(error.message || 'Error checking out. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path></svg> Check Out';
    }
}

function openLeaveModal() {
    const modal = document.getElementById('leaveModal');
    if (modal) {
        modal.classList.add('active');
    }
}

function closeLeaveModal() {
    const modal = document.getElementById('leaveModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

async function submitLeaveRequest(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const data = {
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date'),
        type: formData.get('type'),
        reason: formData.get('reason'),
    };
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    try {
        await apiCall('/api/leaves.php', {
            method: 'POST',
            body: JSON.stringify(data),
        });
        closeLeaveModal();
        form.reset();
        location.reload();
    } catch (error) {
        alert(error.message || 'Error submitting leave request. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function updateLeaveStatus(leaveId, status) {
    const result = await Swal.fire({
        title: '',
        text: `${status} this leave request?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'approved' ? '#10b981' : '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes`,
        cancelButtonText: 'Cancel'
    });
    
    if (!result.isConfirmed) {
        return;
    }
    
    try {
        await apiCall(`/api/leaves-update.php?id=${leaveId}`, {
            method: 'POST',
            body: JSON.stringify({ status }),
        });
        await Swal.fire({
            title: 'Success!',
            text: `Leave request has been ${status}.`,
            icon: 'success',
            confirmButtonColor: '#10b981'
        });
        location.reload();
    } catch (error) {
        await Swal.fire({
            title: 'Error!',
            text: error.message || 'Error updating leave status. Please try again.',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    }
}

function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1)).classList.add('active');
}

// Theme Management
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    updateThemeToggle(theme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
}

function updateThemeToggle(theme) {
    const toggleText = document.querySelector('.theme-toggle-text');
    if (toggleText) {
        toggleText.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme
    initTheme();
    
    const checkInBtn = document.getElementById('checkInBtn');
    if (checkInBtn) {
        checkInBtn.addEventListener('click', checkIn);
    }
    
    const checkOutBtn = document.getElementById('checkOutBtn');
    if (checkOutBtn) {
        checkOutBtn.addEventListener('click', checkOut);
    }
    
    const leaveForm = document.getElementById('leaveForm');
    if (leaveForm) {
        leaveForm.addEventListener('submit', submitLeaveRequest);
    }
    
    const leaveModal = document.getElementById('leaveModal');
    if (leaveModal) {
        leaveModal.addEventListener('click', function(e) {
            if (e.target === leaveModal) {
                closeLeaveModal();
            }
        });
    }
    
    const today = new Date().toISOString().split('T')[0];
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    if (startDateInput) startDateInput.min = today;
    if (endDateInput) endDateInput.min = today;
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
        });
    }
});

const style = document.createElement('style');
style.textContent = `
    .spinner {
        animation: spin 1s linear infinite;
        width: 20px;
        height: 20px;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
