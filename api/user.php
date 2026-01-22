<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = $auth->getUser();
    
    if ($user) {
        unset($user['password']);
        echo json_encode($user);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Not authenticated']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>
