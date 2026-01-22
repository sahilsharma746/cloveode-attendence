<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->logout();
    echo json_encode(['message' => 'Logged out successfully']);
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>
