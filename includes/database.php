<?php
require_once __DIR__ . '/../config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $host = DB_HOST;
            if (strpos($host, ':') !== false) {
                list($host, $port) = explode(':', $host);
                $dsn = "mysql:host={$host};port={$port};dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            } else {
                $socketPath = '/Applications/MAMP/tmp/mysql/mysql.sock';
                if (file_exists($socketPath)) {
                    $dsn = "mysql:unix_socket={$socketPath};dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                } else {
                    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                }
            }
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            error_log("DSN attempted: " . (isset($dsn) ? $dsn : 'unknown'));
            
            if (ini_get('display_errors')) {
                die("<h2>Database Connection Error</h2>" .
                    "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>" .
                    "<p><strong>DSN attempted:</strong> " . htmlspecialchars(isset($dsn) ? $dsn : 'unknown') . "</p>" .
                    "<p><strong>Please check:</strong></p>" .
                    "<ul>" .
                    "<li>MySQL is running in MAMP</li>" .
                    "<li>Database 'workforce_watch' exists</li>" .
                    "<li>Credentials in config.php are correct</li>" .
                    "<li>MySQL port matches MAMP settings (check MAMP → Preferences → Ports)</li>" .
                    "</ul>");
            } else {
                die("Database connection failed. Please check your configuration.");
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}
?>
