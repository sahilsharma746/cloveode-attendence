<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storage.php';

$auth = new Auth();
$storage = new Storage();

if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['message' => 'Not authenticated']);
    exit;
}

$user = $auth->getUser();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $users = $storage->getAllUsers();
    // Remove passwords
    foreach ($users as &$u) {
        unset($u['password']);
    }
    echo json_encode($users);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new user (admin only)
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['username']) || empty($data['password']) || empty($data['full_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username, password, and full name are required']);
        exit;
    }
    
    // Validate role
    $role = $data['role'] ?? 'employee';
    if (!in_array($role, ['employee', 'admin'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid role. Must be "employee" or "admin"']);
        exit;
    }
    
    // Hash password
    $auth = new Auth();
    $hashedPassword = $auth->hashPassword($data['password']);
    
    // Create user
    try {
        $newUser = $storage->createUser([
            'username' => $data['username'],
            'password' => $hashedPassword,
            'full_name' => $data['full_name'],
            'title' => $data['title'] ?? '',
            'role' => $role,
            'leave_balance' => $data['leave_balance'] ?? 24
        ]);
        
        // Remove password from response
        unset($newUser['password']);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'user' => $newUser]);
    } catch (Exception $e) {
        error_log("User creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create user. Username may already exist.']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>
