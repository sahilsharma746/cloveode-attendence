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

if ($_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $data = json_decode(file_get_contents('php://input'), true);
    $status = $data['status'] ?? '';
    
    if (!$leaveId) {
        http_response_code(400);
        echo json_encode(['message' => 'Leave ID required']);
        exit;
    }
    
    if (!in_array($status, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid status']);
        exit;
    }
    
    $leave = $storage->updateLeaveStatus($leaveId, $status);
    echo json_encode($leave);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>
