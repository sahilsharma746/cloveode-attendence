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
    // Get holidays list
    $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
    $upcoming = isset($_GET['upcoming']) && $_GET['upcoming'] === 'true';
    
    if ($upcoming) {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $holidays = $storage->getUpcomingHolidays($limit);
    } else {
        $holidays = $storage->getHolidays($year);
    }
    
    echo json_encode(['success' => true, 'data' => $holidays]);
    
} elseif ($method === 'POST') {
    // Create holiday (admin only)
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['name']) || empty($data['date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name and date are required']);
        exit;
    }
    
    try {
        // Check if holiday already exists for this date
        $existingHoliday = $storage->getHolidayByDate($data['date']);
        if ($existingHoliday) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => "A holiday already exists for this date: \"" . htmlspecialchars($existingHoliday['name']) . "\". Please choose a different date or delete the existing holiday first."
            ]);
            exit;
        }
        
        $holiday = $storage->createHoliday([
            'name' => $data['name'],
            'date' => $data['date'],
            'description' => $data['description'] ?? null
        ]);
        
        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $holiday]);
    } catch (Exception $e) {
        http_response_code(400);
        // Check if it's a table doesn't exist error
        if (strpos($e->getMessage(), 'does not exist') !== false || strpos($e->getMessage(), "Table '") !== false) {
            echo json_encode([
                'success' => false, 
                'message' => 'Holidays table not found. Please run the migration script: ' . BASE_URL . '/add-holidays-table.php'
            ]);
        } 
        // Check if it's a duplicate key error
        else if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'unique_holiday_date') !== false) {
            $existingHoliday = $storage->getHolidayByDate($data['date']);
            if ($existingHoliday) {
                echo json_encode([
                    'success' => false, 
                    'message' => "A holiday already exists for this date: \"" . htmlspecialchars($existingHoliday['name']) . "\". Please choose a different date or delete the existing holiday first."
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Holiday already exists for this date']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } catch (PDOException $e) {
        http_response_code(400);
        // Check if it's a table doesn't exist error
        if (strpos($e->getMessage(), 'does not exist') !== false || strpos($e->getMessage(), "Table '") !== false) {
            echo json_encode([
                'success' => false, 
                'message' => 'Holidays table not found. Please run the migration script: ' . BASE_URL . '/add-holidays-table.php'
            ]);
        } 
        // Check if it's a duplicate key error
        else if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'unique_holiday_date') !== false) {
            $existingHoliday = $storage->getHolidayByDate($data['date']);
            if ($existingHoliday) {
                echo json_encode([
                    'success' => false, 
                    'message' => "A holiday already exists for this date: \"" . htmlspecialchars($existingHoliday['name']) . "\". Please choose a different date or delete the existing holiday first."
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Holiday already exists for this date']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create holiday: ' . $e->getMessage()]);
        }
    }
    
} elseif ($method === 'DELETE') {
    // Delete holiday (admin only)
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Holiday ID required']);
        exit;
    }
    
    $storage->deleteHoliday($id);
    echo json_encode(['success' => true, 'message' => 'Holiday deleted successfully']);
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
