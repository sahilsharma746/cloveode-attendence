<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'workforce_watch');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');


define('BASE_URL', '/cloveode-attendence');
define('APP_NAME', 'Cloveode Attendance System');
if (session_status() === PHP_SESSION_NONE) {
    try {
        session_start();
    } catch (Exception $e) {
        error_log("Session start failed: " . $e->getMessage());
    }
}

date_default_timezone_set('Asia/Kolkata');
?>
