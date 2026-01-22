<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/storage.php';

$auth = new Auth();
$storage = new Storage();

if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user = $auth->getUser();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $activeOnly = !isset($_GET['active_only']) || $_GET['active_only'] !== 'false';
    
    $notifications = $storage->getNotifications($limit, $activeOnly);
    
    echo json_encode(['success' => true, 'data' => $notifications]);
    
} elseif ($method === 'POST') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['title']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title and message are required']);
        exit;
    }
    
    try {
        $notification = $storage->createNotification([
            'title' => trim($data['title'] ?? ''),
            'message' => trim($data['message'] ?? ''),
            'type' => $data['type'] ?? 'info',
            'created_by' => $user['id'],
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $notification]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Notification creation PDO error: " . $e->getMessage());
        $message = 'Database error occurred. ';
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), 'Table') !== false) {
            $message .= 'Notifications table not found. Please run the migration script: ' . BASE_URL . '/create-notifications-table.php';
        } else {
            $message .= 'Please check your database connection and try again.';
        }
        echo json_encode(['success' => false, 'message' => $message]);
    } catch (Exception $e) {
        http_response_code(400);
        error_log("Notification creation error: " . $e->getMessage());
        if (strpos($e->getMessage(), 'does not exist') !== false || strpos($e->getMessage(), 'Table') !== false) {
            echo json_encode([
                'success' => false, 
                'message' => 'Notifications table not found. Please run the migration script: ' . BASE_URL . '/create-notifications-table.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
} elseif ($method === 'PATCH') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $notification = $storage->updateNotification($id, $data);
        
        if ($notification) {
            echo json_encode(['success' => true, 'data' => $notification]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} elseif ($method === 'DELETE') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        exit;
    }
    
    $result = $storage->deleteNotification($id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
