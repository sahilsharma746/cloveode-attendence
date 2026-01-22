<?php
require_once __DIR__ . '/database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function hashPassword($password) {
        $salt = bin2hex(random_bytes(16));
        $hash = hash('sha256', $password . $salt);
        return $hash . '.' . $salt;
    }

    public function verifyPassword($password, $storedHash) {
        list($hash, $salt) = explode('.', $storedHash);
        $verifyHash = hash('sha256', $password . $salt);
        return hash_equals($hash, $verifyHash);
    }

    public function handleProfilePictureUpload($file) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error'];
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds 2MB limit.'];
        }
        
        $uploadsDir = __DIR__ . '/../uploads/profiles';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('profile_', true) . '.' . $extension;
        $filepath = $uploadsDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Failed to save uploaded file.'];
        }
        
        return ['success' => true, 'filename' => 'uploads/profiles/' . $filename];
    }

    public function register($username, $password, $fullName, $title = '', $role = 'employee', $profilePicture = null) {
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE LOWER(username) = LOWER(?)",
            [$username]
        );

        if ($existingUser) {
            return ['success' => false, 'error' => 'Username already exists'];
        }

        if (empty($username) || empty($password) || empty($fullName)) {
            return ['success' => false, 'error' => 'All required fields must be filled'];
        }

        if (strlen($username) < 3) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        $hashedPassword = $this->hashPassword($password);

        try {
            // Check if profile_picture column exists, if not use NULL
            $columns = "username, password, full_name, title, role, leave_balance";
            $values = "?, ?, ?, ?, ?, ?";
            $params = [$username, $hashedPassword, $fullName, $title, $role, 20];
            
            if ($profilePicture !== null) {
                $columns .= ", profile_picture";
                $values .= ", ?";
                $params[] = $profilePicture;
            }
            
            $this->db->query(
                "INSERT INTO users ({$columns}) VALUES ({$values})",
                $params
            );

            $userId = $this->db->lastInsertId();
            
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ?",
                [$userId]
            );

            return ['success' => true, 'user' => $user];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Registration failed. Please try again.'];
        }
    }

    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE LOWER(username) = LOWER(?)",
            [$username]
        );

        if (!$user || !$this->verifyPassword($password, $user['password'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        return $user;
    }

    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }

    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user']);
    }

    public function getUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return $_SESSION['user'];
    }

    public function updateSessionUser($userData) {
        if ($this->isAuthenticated() && $userData) {
            $_SESSION['user'] = $userData;
        }
    }

    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }

    public function isAdmin() {
        $user = $this->getUser();
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }

    public function requireAdmin() {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
    }
}
?>
