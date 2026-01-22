<?php
function getUserAvatar($user, $size = 'medium') {
    $sizeClasses = [
        'small' => ['size' => '40px', 'font' => '16px'],
        'medium' => ['size' => '48px', 'font' => '18px'],
        'org' => ['size' => '80px', 'font' => '32px'],
        'large' => ['size' => '250px', 'font' => '200px']
    ];
    
    $sizes = $sizeClasses[$size] ?? $sizeClasses['medium'];
    $sizeStyle = "width: {$sizes['size']}; height: {$sizes['size']}; font-size: {$sizes['font']};";
    
    if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/../' . $user['profile_picture'])) {
        return '<img src="' . BASE_URL . '/' . htmlspecialchars($user['profile_picture']) . '" 
                    alt="' . htmlspecialchars($user['full_name']) . '" 
                    class="user-avatar-img"
                    style="' . $sizeStyle . 'object-fit: cover; border: 2px solid #e2e8f0; display: block;">';
    } else {
        $initial = strtoupper(substr($user['full_name'], 0, 1));
        return '<div class="user-avatar-initial" style="' . $sizeStyle . 'background: linear-gradient(135deg, #3b82f6, #8b5cf6); 
                    display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; 
                    border: 2px solid #e2e8f0;">' . htmlspecialchars($initial) . '</div>';
    }
}
?>
