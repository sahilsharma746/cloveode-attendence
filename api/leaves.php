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
$method = $_SERVER['REQUEST_METHOD'];
$pathInfo = $_SERVER['PATH_INFO'] ?? '';

if ($method === 'GET') {
    // Get leaves list
    if ($user['role'] === 'admin') {
        $userId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;
        $leaves = $storage->getLeaves($userId);
    } else {
        $leaves = $storage->getLeaves($user['id']);
    }
    
    echo json_encode($leaves);
    
} elseif ($method === 'POST') {
    // Create leave request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['start_date']) || empty($data['end_date']) || empty($data['reason'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required fields']);
        exit;
    }
    
    $leave = $storage->createLeave([
        'user_id' => $user['id'],
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'reason' => $data['reason'],
        'type' => $data['type'] ?? 'casual',
        'status' => 'pending'
    ]);
    
    http_response_code(201);
    echo json_encode($leave);
    
} elseif ($method === 'PATCH' && preg_match('#/leaves/(\d+)/status#', $pathInfo, $matches)) {
    // Update leave status (admin only)
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['message' => 'Admin access required']);
        exit;
    }
    
    $leaveId = (int)$matches[1];
    $data = json_decode(file_get_contents('php://input'), true);
    $status = $data['status'] ?? '';
    
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
