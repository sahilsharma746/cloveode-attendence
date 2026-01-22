<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();
if ($auth->isAuthenticated()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    
    // Handle profile picture upload
    $profilePicture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profilePicture = $auth->handleProfilePictureUpload($_FILES['profile_picture']);
        if (!$profilePicture['success']) {
            $error = $profilePicture['error'];
        } else {
            $profilePicture = $profilePicture['filename'];
        }
    }
    
    if (!$error && $password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else if (!$error) {
        $result = $auth->register($username, $password, $fullName, $title, 'employee', $profilePicture);
        
        if ($result['success']) {
            if ($auth->login($username, $password)) {
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            } else {
                $success = 'Registration successful! Please login.';
            }
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = 'Cloveode Attendance System';
include __DIR__ . '/includes/header.php';
?>
<div class="login-container">
    <div class="login-background"></div>
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h1 class="login-title">Create Account</h1>
            <p class="login-subtitle">Sign up for <?php echo APP_NAME; ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_picture">Profile Picture (Optional)</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" onchange="previewProfilePicture(this)">
                <small class="form-help">JPG, PNG or GIF (Max 2MB)</small>
                <div id="profilePreview" style="margin-top: 10px; display: none;">
                    <img id="profilePreviewImg" src="" alt="Preview" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required autofocus value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <small class="form-help">At least 3 characters</small>
            </div>
            
            <div class="form-group">
                <label for="title">Job Title (Optional)</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <small class="form-help">At least 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <script>
        function previewProfilePicture(input) {
            const preview = document.getElementById('profilePreview');
            const previewImg = document.getElementById('profilePreviewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
        </script>
        
        <div class="login-footer">
            <p>Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Sign in</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
