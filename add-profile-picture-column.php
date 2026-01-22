<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    // Check if profile_picture column already exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    
    if (empty($columns)) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER title";
        $db->query($sql);
        echo "<h2>Profile Picture Column Added Successfully!</h2>";
        echo "<p>The profile_picture column has been added to the users table.</p>";
    } else {
        echo "<h2>Profile Picture Column Already Exists</h2>";
        echo "<p>The profile_picture column is already in the users table.</p>";
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/uploads/profiles';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        echo "<p>Created uploads directory: {$uploadsDir}</p>";
    } else {
        echo "<p>Uploads directory already exists: {$uploadsDir}</p>";
    }
    
    echo "<p><a href='" . BASE_URL . "/register.php'>Go to Registration</a></p>";
    echo "<p><a href='" . BASE_URL . "/admin.php'>Go to Admin Panel</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error Adding Profile Picture Column</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
