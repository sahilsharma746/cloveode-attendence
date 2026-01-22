<?php
ob_start();

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/storage.php';

    $auth = new Auth();
    $storage = new Storage();

    if (!$auth->isAuthenticated()) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $user = $auth->getUser();
    
    if (!$user || !isset($user['id'])) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid user session. Please log in again.']);
        exit;
    }
    
    $dbUser = $storage->getUser($user['id']);
    if (!$dbUser) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User account not found. Please log in again.']);
        exit;
    }
    
    $user = $dbUser;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'POST' && $action === 'check-in') {
        date_default_timezone_set('Asia/Kolkata');
        
        $today = date('Y-m-d');
        
        $existing = $storage->getAttendanceByDate($user['id'], $today);
        if ($existing) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Already checked in today']);
            exit;
        }
        
        $checkInTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $expectedCheckIn = new DateTime($today . ' 10:00:00', new DateTimeZone('Asia/Kolkata'));
        
        $status = 'present';
        $lateMinutes = null;
        
        if ($checkInTime > $expectedCheckIn) {
            $status = 'late';
            $secondsLate = $checkInTime->getTimestamp() - $expectedCheckIn->getTimestamp();
            $lateMinutes = (int)round($secondsLate / 60);
        }
        
        $attendance = $storage->createAttendance([
            'user_id' => $user['id'],
            'date' => $today,
            'check_in' => $checkInTime->format('Y-m-d H:i:s'),
            'status' => $status,
            'late_minutes' => $lateMinutes
        ]);
        
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $attendance]);
        
    } elseif ($method === 'POST' && $action === 'check-out') {
        date_default_timezone_set('Asia/Kolkata');
        
        $today = date('Y-m-d');
        
        $existing = $storage->getAttendanceByDate($user['id'], $today);
        if (!$existing) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Not checked in today']);
            exit;
        }
        if ($existing['check_out']) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Already checked out today']);
            exit;
        }
        
        $checkOutTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $attendance = $storage->updateAttendanceCheckOut($existing['id'], $checkOutTime->format('Y-m-d H:i:s'));
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $attendance]);
        
    } elseif ($method === 'GET') {
        $targetUserId = $user['id'];
        
        if ($user['role'] === 'admin' && isset($_GET['userId'])) {
            $targetUserId = (int)$_GET['userId'];
        }
        
        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        
        if ($user['role'] === 'admin' && !isset($_GET['userId'])) {
            $records = $storage->getAttendance(null, $month, $year);
        } else {
            $records = $storage->getAttendance($targetUserId, $month, $year);
        }
        
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $records]);
        
    } else {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(500);
    
    $errorCode = $e->getCode();
    $errorInfo = $e->errorInfo();
    $sqlState = isset($errorInfo[0]) ? $errorInfo[0] : $errorCode;
    
    if ($sqlState == '23000' || strpos($e->getMessage(), 'foreign key constraint') !== false) {
        echo json_encode([
            'success' => false,
            'message' => 'Database integrity error. Your session may be invalid. Please log out and log in again.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again.'
        ]);
    }
    error_log('Attendance API PDO Error: ' . $e->getMessage() . ' | Code: ' . $errorCode . ' | SQLSTATE: ' . $sqlState);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
    error_log('Attendance API Error: ' . $e->getMessage());
}
?>
