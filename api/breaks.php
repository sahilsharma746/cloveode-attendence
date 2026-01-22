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

    if ($method === 'POST' && $action === 'start') {
        date_default_timezone_set('Asia/Kolkata');
        
        $today = date('Y-m-d');
        $attendance = $storage->getAttendanceByDate($user['id'], $today);
        
        if (!$attendance || !$attendance['check_in']) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You must check in first before taking a break']);
            exit;
        }
        
        if ($attendance['check_out']) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You have already checked out']);
            exit;
        }
        
        // Check if there's an active break
        $activeBreak = $storage->getActiveBreak($user['id'], $attendance['id']);
        if ($activeBreak) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You already have an active break']);
            exit;
        }
        
        $breakStart = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $breakType = $_POST['break_type'] ?? 'break';
        $reason = $_POST['reason'] ?? null;
        $expectedDuration = isset($_POST['expected_duration_minutes']) ? (int)$_POST['expected_duration_minutes'] : null;
        
        $break = $storage->startBreak([
            'user_id' => $user['id'],
            'attendance_id' => $attendance['id'],
            'break_start' => $breakStart->format('Y-m-d H:i:s'),
            'break_type' => $breakType,
            'reason' => $reason,
            'expected_duration_minutes' => $expectedDuration
        ]);
        
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $break]);
        
    } elseif ($method === 'POST' && $action === 'end') {
        date_default_timezone_set('Asia/Kolkata');
        
        $today = date('Y-m-d');
        $attendance = $storage->getAttendanceByDate($user['id'], $today);
        
        if (!$attendance) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No attendance record found']);
            exit;
        }
        
        $activeBreak = $storage->getActiveBreak($user['id'], $attendance['id']);
        if (!$activeBreak) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No active break found']);
            exit;
        }
        
        $breakEnd = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $break = $storage->endBreak($activeBreak['id'], $breakEnd->format('Y-m-d H:i:s'));
        
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $break]);
        
    } elseif ($method === 'GET' && $action === 'active') {
        $today = date('Y-m-d');
        $attendance = $storage->getAttendanceByDate($user['id'], $today);
        
        if (!$attendance) {
            ob_end_clean();
            echo json_encode(['success' => true, 'data' => null]);
            exit;
        }
        
        $activeBreak = $storage->getActiveBreak($user['id'], $attendance['id']);
        
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $activeBreak]);
        
    } elseif ($method === 'GET' && $action === 'list') {
        $today = date('Y-m-d');
        $attendance = $storage->getAttendanceByDate($user['id'], $today);
        
        if (!$attendance) {
            ob_end_clean();
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        
        $breaks = $storage->getBreaksByAttendance($attendance['id']);
        
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => $breaks]);
        
    } else {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
    error_log('Breaks API PDO Error: ' . $e->getMessage());
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
    error_log('Breaks API Error: ' . $e->getMessage());
}
?>
