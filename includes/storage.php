<?php
require_once __DIR__ . '/database.php';

class Storage {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getUser($id) {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public function getUserByUsername($username) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE LOWER(username) = LOWER(?)",
            [$username]
        );
    }

    public function createUser($data) {
        $this->db->query(
            "INSERT INTO users (username, password, full_name, role, title, leave_balance) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                strtolower($data['username']),
                $data['password'],
                $data['full_name'],
                $data['role'] ?? 'employee',
                $data['title'] ?? null,
                $data['leave_balance'] ?? 24
            ]
        );
        return $this->getUser($this->db->lastInsertId());
    }

    public function getAllUsers() {
        return $this->db->fetchAll("SELECT * FROM users ORDER BY id");
    }

    public function createAttendance($data) {
        $this->db->query(
            "INSERT INTO attendance (user_id, date, check_in, status, late_minutes) 
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['user_id'],
                $data['date'],
                $data['check_in'] ?? null,
                $data['status'] ?? 'present',
                $data['late_minutes'] ?? null
            ]
        );
        return $this->getAttendanceById($this->db->lastInsertId());
    }

    public function getAttendanceById($id) {
        return $this->db->fetchOne("SELECT * FROM attendance WHERE id = ?", [$id]);
    }

    public function getAttendanceByDate($userId, $date) {
        return $this->db->fetchOne(
            "SELECT * FROM attendance WHERE user_id = ? AND date = ?",
            [$userId, $date]
        );
    }
    
    public function getAttendance($userId = null, $month = null, $year = null) {
        $sql = "SELECT * FROM attendance WHERE 1=1";
        $params = [];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        if ($month !== null && $year !== null) {
            $sql .= " AND MONTH(date) = ? AND YEAR(date) = ?";
            $params[] = $month;
            $params[] = $year;
        }

        $sql .= " ORDER BY date DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function updateAttendanceCheckOut($id, $checkOutTime) {
        $this->db->query(
            "UPDATE attendance SET check_out = ?, status = 'out' WHERE id = ?",
            [$checkOutTime, $id]
        );
        return $this->getAttendanceById($id);
    }

    public function createLeave($data) {
        $this->db->query(
            "INSERT INTO leaves (user_id, start_date, end_date, reason, status, type) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'],
                $data['start_date'],
                $data['end_date'],
                $data['reason'],
                $data['status'] ?? 'pending',
                $data['type'] ?? 'casual'
            ]
        );
        return $this->getLeaveById($this->db->lastInsertId());
    }
    public function getLeaveById($id) {
        return $this->db->fetchOne("SELECT * FROM leaves WHERE id = ?", [$id]);
    }

    public function getLeaves($userId = null) {
        $sql = "SELECT * FROM leaves WHERE 1=1";
        $params = [];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY start_date DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function updateLeaveStatus($id, $status) {
        $this->db->query(
            "UPDATE leaves SET status = ? WHERE id = ?",
            [$status, $id]
        );
        return $this->getLeaveById($id);
    }

    public function deleteLeavesByStatus($status) {
        if (!in_array($status, ['approved', 'rejected'])) {
            return false;
        }
        $this->db->query(
            "DELETE FROM leaves WHERE status = ?",
            [$status]
        );
        return true;
    }

    public function getUsersCurrentlyOnLeave($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $sql = "SELECT l.*, u.full_name, u.username, u.title, u.profile_picture 
                FROM leaves l 
                INNER JOIN users u ON l.user_id = u.id 
                WHERE l.status = 'approved' 
                AND l.start_date <= ? 
                AND l.end_date >= ? 
                ORDER BY u.full_name ASC";
        
        return $this->db->fetchAll($sql, [$date, $date]);
    }

   
    public function getAttendanceWithUsers($userId = null, $month = null, $year = null) {
        $sql = "SELECT a.*, u.full_name, u.username, u.title 
                FROM attendance a 
                INNER JOIN users u ON a.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if ($userId !== null) {
            $sql .= " AND a.user_id = ?";
            $params[] = $userId;
        }

        if ($month !== null && $year !== null) {
            $sql .= " AND MONTH(a.date) = ? AND YEAR(a.date) = ?";
            $params[] = $month;
            $params[] = $year;
        }

        $sql .= " ORDER BY a.date DESC, u.full_name ASC";
        return $this->db->fetchAll($sql, $params);
    }


    public function getUserAttendanceStats($userId, $month = null, $year = null) {
        $attendanceSql = "SELECT COUNT(DISTINCT date) as present_days 
                         FROM attendance 
                         WHERE user_id = ?";
        $attendanceParams = [$userId];

        if ($month !== null && $year !== null) {
            $attendanceSql .= " AND MONTH(date) = ? AND YEAR(date) = ?";
            $attendanceParams[] = $month;
            $attendanceParams[] = $year;
        }

        $presentDays = $this->db->fetchOne($attendanceSql, $attendanceParams)['present_days'] ?? 0;

        $leavesSql = "SELECT start_date, end_date 
                     FROM leaves 
                     WHERE user_id = ? AND status = 'approved'";
        $leavesParams = [$userId];

        if ($month !== null && $year !== null) {
            $lastDay = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
            $firstDay = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
            $leavesSql .= " AND start_date <= ? AND end_date >= ?";
            $leavesParams[] = $lastDay;
            $leavesParams[] = $firstDay;
        }

        $leaves = $this->db->fetchAll($leavesSql, $leavesParams);
        $leaveDays = 0;

        foreach ($leaves as $leave) {
            $start = new DateTime($leave['start_date']);
            $end = new DateTime($leave['end_date']);
            $interval = $start->diff($end);
            $days = $interval->days + 1; 

            if ($month !== null && $year !== null) {
                $filterStart = new DateTime("$year-$month-01");
                $filterEnd = new DateTime($filterStart->format('Y-m-t'));
                
                if ($start < $filterStart) $start = $filterStart;
                if ($end > $filterEnd) $end = $filterEnd;
                
                if ($start <= $end) {
                    $interval = $start->diff($end);
                    $days = $interval->days + 1;
                } else {
                    $days = 0;
                }
            }

            $leaveDays += $days;
        }

        return [
            'present_days' => (int)$presentDays,
            'leave_days' => $leaveDays,
            'total_working_days' => (int)$presentDays + $leaveDays
        ];
    }


    public function getAllUsersAttendanceStats($month = null, $year = null) {
        $users = $this->getAllUsers();
        $stats = [];

        foreach ($users as $user) {
            $stats[$user['id']] = array_merge(
                $user,
                $this->getUserAttendanceStats($user['id'], $month, $year)
            );
        }

        return $stats;
    }

    // Holiday management methods
    public function createHoliday($data) {
        // Check if holidays table exists first
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'holidays'")->rowCount() > 0;
            if (!$tableExists) {
                throw new Exception("Holidays table does not exist. Please run the migration script at: " . BASE_URL . "/add-holidays-table.php");
            }
        } catch (Exception $e) {
            throw $e;
        }
        
        $this->db->query(
            "INSERT INTO holidays (name, date, description) 
             VALUES (?, ?, ?)",
            [
                $data['name'],
                $data['date'],
                $data['description'] ?? null
            ]
        );
        return $this->getHolidayById($this->db->lastInsertId());
    }

    public function getHolidayById($id) {
        return $this->db->fetchOne("SELECT * FROM holidays WHERE id = ?", [$id]);
    }

    public function getHolidayByDate($date) {
        return $this->db->fetchOne("SELECT * FROM holidays WHERE date = ?", [$date]);
    }

    public function getHolidays($year = null) {
        // Check if holidays table exists first
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'holidays'")->rowCount() > 0;
            if (!$tableExists) {
                return []; // Return empty array if table doesn't exist yet
            }
        } catch (PDOException $e) {
            return []; // Return empty array on any error
        }
        
        $sql = "SELECT * FROM holidays WHERE 1=1";
        $params = [];

        if ($year !== null) {
            $sql .= " AND YEAR(date) = ?";
            $params[] = $year;
        }

        $sql .= " ORDER BY date ASC";
        try {
            return $this->db->fetchAll($sql, $params);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function deleteHoliday($id) {
        $this->db->query("DELETE FROM holidays WHERE id = ?", [$id]);
        return true;
    }

    public function getUpcomingHolidays($limit = 10) {
        // Check if holidays table exists first
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'holidays'")->rowCount() > 0;
            if (!$tableExists) {
                return []; // Return empty array if table doesn't exist yet
            }
        } catch (PDOException $e) {
            return []; // Return empty array on any error
        }
        
        $today = date('Y-m-d');
        $limit = (int)$limit; // Ensure it's an integer for safety
        try {
            return $this->db->fetchAll(
                "SELECT * FROM holidays WHERE date >= ? ORDER BY date ASC LIMIT " . $limit,
                [$today]
            );
        } catch (PDOException $e) {
            // If table doesn't exist or any other error, return empty array
            return [];
        }
    }

    // Notification management methods
    public function createNotification($data) {
        // Check if notifications table exists first
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
            if (!$tableExists) {
                throw new Exception("Notifications table does not exist. Please run the migration script at: " . BASE_URL . "/create-notifications-table.php");
            }
        } catch (PDOException $e) {
            throw new Exception("Database error checking for notifications table: " . $e->getMessage());
        } catch (Exception $e) {
            throw $e;
        }
        
        try {
            $this->db->query(
                "INSERT INTO notifications (title, message, type, created_by, is_active) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $data['title'],
                    $data['message'],
                    $data['type'] ?? 'info',
                    $data['created_by'],
                    isset($data['is_active']) ? (int)$data['is_active'] : 1
                ]
            );
            return $this->getNotificationById($this->db->lastInsertId());
        } catch (PDOException $e) {
            error_log("Notification creation error: " . $e->getMessage());
            throw new Exception("Failed to create notification: " . $e->getMessage());
        }
    }

    public function getNotificationById($id) {
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
            if (!$tableExists) {
                return null;
            }
            return $this->db->fetchOne(
                "SELECT n.*, u.full_name as created_by_name 
                 FROM notifications n 
                 LEFT JOIN users u ON n.created_by = u.id 
                 WHERE n.id = ?",
                [$id]
            );
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getNotifications($limit = null, $activeOnly = true) {
        // Check if notifications table exists first
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
            if (!$tableExists) {
                return []; // Return empty array if table doesn't exist yet
            }
        } catch (PDOException $e) {
            return []; // Return empty array on any error
        }
        
        $sql = "SELECT n.*, u.full_name as created_by_name 
                FROM notifications n 
                LEFT JOIN users u ON n.created_by = u.id 
                WHERE 1=1";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND n.is_active = 1";
        }

        $sql .= " ORDER BY n.created_at DESC";

        if ($limit !== null) {
            $limit = (int)$limit;
            $sql .= " LIMIT " . $limit;
        }

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateNotification($id, $data) {
        $updates = [];
        $params = [];

        if (isset($data['title'])) {
            $updates[] = "title = ?";
            $params[] = $data['title'];
        }
        if (isset($data['message'])) {
            $updates[] = "message = ?";
            $params[] = $data['message'];
        }
        if (isset($data['type'])) {
            $updates[] = "type = ?";
            $params[] = $data['type'];
        }
        if (isset($data['is_active'])) {
            $updates[] = "is_active = ?";
            $params[] = $data['is_active'];
        }

        if (empty($updates)) {
            return $this->getNotificationById($id);
        }

        $params[] = $id;
        $sql = "UPDATE notifications SET " . implode(", ", $updates) . " WHERE id = ?";
        
        $this->db->query($sql, $params);
        return $this->getNotificationById($id);
    }

    public function deleteNotification($id) {
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
            if (!$tableExists) {
                return false;
            }
            $this->db->query("DELETE FROM notifications WHERE id = ?", [$id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getNewNotificationsCount($days = 7, $lastViewed = null) {
        try {
            $tableExists = $this->db->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
            if (!$tableExists) {
                return 0;
            }
        } catch (PDOException $e) {
            return 0;
        }
        
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM notifications 
                    WHERE is_active = 1";
            $params = [];
            
            if ($lastViewed) {
                $sql .= " AND created_at > ?";
                $params[] = $lastViewed;
            } else {
                $dateThreshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                $sql .= " AND created_at >= ?";
                $params[] = $dateThreshold;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Break management methods
    private function breaksTableExists() {
        try {
            $result = $this->db->query("SHOW TABLES LIKE 'breaks'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function startBreak($data) {
        if (!$this->breaksTableExists()) {
            throw new Exception("Breaks table does not exist. Please run the migration script at: " . BASE_URL . "/create-breaks-table.php");
        }
        
        $this->db->query(
            "INSERT INTO breaks (user_id, attendance_id, break_start, break_type, reason, expected_duration_minutes) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'],
                $data['attendance_id'],
                $data['break_start'],
                $data['break_type'] ?? 'break',
                $data['reason'] ?? null,
                $data['expected_duration_minutes'] ?? null
            ]
        );
        return $this->getBreakById($this->db->lastInsertId());
    }

    public function getBreakById($id) {
        if (!$this->breaksTableExists()) {
            return null;
        }
        return $this->db->fetchOne("SELECT * FROM breaks WHERE id = ?", [$id]);
    }

    public function getActiveBreak($userId, $attendanceId) {
        if (!$this->breaksTableExists()) {
            return null;
        }
        return $this->db->fetchOne(
            "SELECT * FROM breaks 
             WHERE user_id = ? AND attendance_id = ? AND break_end IS NULL 
             ORDER BY break_start DESC LIMIT 1",
            [$userId, $attendanceId]
        );
    }

    public function endBreak($breakId, $breakEndTime) {
        if (!$this->breaksTableExists()) {
            throw new Exception("Breaks table does not exist. Please run the migration script at: " . BASE_URL . "/create-breaks-table.php");
        }
        
        $this->db->query(
            "UPDATE breaks SET break_end = ? WHERE id = ?",
            [$breakEndTime, $breakId]
        );
        return $this->getBreakById($breakId);
    }

    public function getBreaksByAttendance($attendanceId) {
        if (!$this->breaksTableExists()) {
            return [];
        }
        return $this->db->fetchAll(
            "SELECT * FROM breaks WHERE attendance_id = ? ORDER BY break_start DESC",
            [$attendanceId]
        );
    }

    public function getBreaksByUser($userId, $date = null) {
        if (!$this->breaksTableExists()) {
            return [];
        }
        
        $sql = "SELECT b.*, a.date as attendance_date 
                FROM breaks b 
                INNER JOIN attendance a ON b.attendance_id = a.id 
                WHERE b.user_id = ?";
        $params = [$userId];
        
        if ($date) {
            $sql .= " AND a.date = ?";
            $params[] = $date;
        }
        
        $sql .= " ORDER BY b.break_start DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getEmployeesInOffice($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        // Check if breaks table exists
        $breaksTableExists = $this->breaksTableExists();
        
        // Get employees who checked in today and haven't checked out
        // and don't have an active break (if breaks table exists)
        if ($breaksTableExists) {
            $sql = "SELECT DISTINCT u.*, a.id as attendance_id, a.check_in, a.check_out
                    FROM users u
                    INNER JOIN attendance a ON u.id = a.user_id
                    WHERE a.date = ?
                    AND a.check_in IS NOT NULL
                    AND a.check_out IS NULL
                    AND u.role != 'admin'
                    AND NOT EXISTS (
                        SELECT 1 FROM breaks b 
                        WHERE b.user_id = u.id 
                        AND b.attendance_id = a.id 
                        AND b.break_end IS NULL
                    )
                    ORDER BY u.full_name ASC";
        } else {
            // If breaks table doesn't exist, just show all checked-in employees
            $sql = "SELECT DISTINCT u.*, a.id as attendance_id, a.check_in, a.check_out
                    FROM users u
                    INNER JOIN attendance a ON u.id = a.user_id
                    WHERE a.date = ?
                    AND a.check_in IS NOT NULL
                    AND a.check_out IS NULL
                    AND u.role != 'admin'
                    ORDER BY u.full_name ASC";
        }
        
        try {
            return $this->db->fetchAll($sql, [$date]);
        } catch (PDOException $e) {
            error_log("Error getting employees in office: " . $e->getMessage());
            return [];
        }
    }

    public function getEmployeesOnBreak($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        // Check if breaks table exists
        if (!$this->breaksTableExists()) {
            return [];
        }
        
        $sql = "SELECT u.*, a.id as attendance_id, a.check_in, b.id as break_id, 
                       b.break_start, b.break_type, b.reason, b.expected_duration_minutes
                FROM users u
                INNER JOIN attendance a ON u.id = a.user_id
                INNER JOIN breaks b ON a.id = b.attendance_id
                WHERE a.date = ?
                AND a.check_in IS NOT NULL
                AND a.check_out IS NULL
                AND b.break_end IS NULL
                AND u.role != 'admin'
                ORDER BY b.break_start DESC";
        
        try {
            return $this->db->fetchAll($sql, [$date]);
        } catch (PDOException $e) {
            error_log("Error getting employees on break: " . $e->getMessage());
            return [];
        }
    }

    public function getAllBreaksWithUsers($userId = null, $month = null, $year = null) {
        // Check if breaks table exists
        if (!$this->breaksTableExists()) {
            return [];
        }
        
        if ($month === null) {
            $month = date('n');
        }
        if ($year === null) {
            $year = date('Y');
        }
        
        $sql = "SELECT b.*, u.full_name, u.title, a.date as attendance_date, a.check_in, a.check_out
                FROM breaks b
                INNER JOIN attendance a ON b.attendance_id = a.id
                INNER JOIN users u ON b.user_id = u.id
                WHERE MONTH(a.date) = ? AND YEAR(a.date) = ?";
        
        $params = [$month, $year];
        
        if ($userId !== null) {
            $sql .= " AND b.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY a.date DESC, b.break_start DESC";
        
        try {
            return $this->db->fetchAll($sql, $params);
        } catch (PDOException $e) {
            error_log("Error getting all breaks with users: " . $e->getMessage());
            return [];
        }
    }
}
?>
