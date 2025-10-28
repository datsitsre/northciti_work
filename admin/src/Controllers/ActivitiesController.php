<?php

// admin/src/Controllers/ActivitiesController.php - Activities Management Controller

class ActivitiesController {
    private $db;
    private $user;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
    }

    public static function index() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        // Get filter parameters
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 25), 100);
        $action = $_GET['action'] ?? '';
        $userId = (int)($_GET['user_id'] ?? 0);
        $targetType = $_GET['target_type'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $search = $_GET['search'] ?? '';

        // Get activities with filters
        $activities = $controller->getActivities($page, $perPage, $action, $userId, $targetType, $dateFrom, $dateTo, $search);
        
        // Get filter options
        $actions = $controller->getAvailableActions();
        $users = $controller->getActiveUsers();
        $targetTypes = $controller->getTargetTypes();
        
        // Get statistics
        $stats = $controller->getActivityStats();

        // Set page data
        LayoutHelper::setPageData(
            'Activity Log',
            'Monitor all system activities and user actions',
            [
                ['label' => 'Activity Logs']
            ]
        );
        
        // Render view
        LayoutHelper::render('activities/index', [
            'activities' => $activities,
            'search' => $search,
            'stats' => $stats,
            'actions' => $actions,
            'users' => $users,
            'targetTypes' => $targetTypes
        ]);
    }

    public static function show() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        $activityId = (int)($_GET['id'] ?? 0);
        if (!$activityId) {
            redirect(Router::url('activities'));
            return;
        }

        $activity = $controller->getActivityDetails($activityId);
        if (!$activity) {
            redirect(Router::url('activities'));
            return;
        }

        // Set page data
        LayoutHelper::setPageData(
            'Activity Details',
            'Detailed information about this activity',
            [
                ['label' => 'All Activity ogs']
            ]
        );
        
        // Render view
        LayoutHelper::render('activities/show', [
            'activity' => $activity
        ]);
    }

    public static function export() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        $format = $_GET['format'] ?? 'csv';
        $filters = [
            'action' => $_GET['action'] ?? '',
            'user_id' => (int)($_GET['user_id'] ?? 0),
            'target_type' => $_GET['target_type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        try {
            $filename = $controller->exportActivities($format, $filters);
            
            // Log the export activity
            $controller->db->execute("INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) VALUES (?, 'activities_exported', 'system', 0, ?, ?, ?, NOW())", [
                $controller->user['id'],
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode(['format' => $format, 'filters' => $filters])
            ]);

            // Force download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize(__DIR__ . '/../../exports/' . $filename));
            readfile(__DIR__ . '/../../exports/' . $filename);
            
            // Clean up file after download
            unlink(__DIR__ . '/../../exports/' . $filename);
            exit;
            
        } catch (Exception $e) {
            $error = 'Export failed: ' . $e->getMessage();
            redirect(Router::url('activities') . '?error=' . urlencode($error));
        }
    }

    public static function cleanup() {
        $controller = new self();
        
        if (!AuthController::isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(Router::url('activities'));
            return;
        }

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            redirect(Router::url('activities') . '?error=' . urlencode('Invalid security token'));
            return;
        }

        $days = (int)($_POST['days'] ?? 90);
        if ($days < 1 || $days > 365) {
            redirect(Router::url('activities') . '?error=' . urlencode('Invalid cleanup period'));
            return;
        }

        try {
            $deleted = $controller->cleanupOldActivities($days);
            
            // Log the cleanup activity
            $controller->db->execute("INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) VALUES (?, 'activities_cleanup', 'system', 0, ?, ?, ?, NOW())", [
                $controller->user['id'],
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode(['days' => $days, 'deleted_count' => $deleted])
            ]);

            redirect(Router::url('activities') . '?success=' . urlencode("Cleanup completed. Deleted {$deleted} old activity records."));
            
        } catch (Exception $e) {
            redirect(Router::url('activities') . '?error=' . urlencode('Cleanup failed: ' . $e->getMessage()));
        }
    }

    private function getActivities($page, $perPage, $action, $userId, $targetType, $dateFrom, $dateTo, $search) {
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE conditions
        $whereConditions = [];
        $params = [];
        
        if (!empty($action)) {
            $whereConditions[] = "al.action = ?";
            $params[] = $action;
        }
        
        if ($userId > 0) {
            $whereConditions[] = "al.user_id = ?";
            $params[] = $userId;
        }
        
        if (!empty($targetType)) {
            $whereConditions[] = "al.target_type = ?";
            $params[] = $targetType;
        }
        
        if (!empty($dateFrom)) {
            $whereConditions[] = "DATE(al.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if (!empty($dateTo)) {
            $whereConditions[] = "DATE(al.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        if (!empty($search)) {
            $whereConditions[] = "(al.action LIKE ? OR al.target_type LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM activity_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        
        // Get activities with user information
        $sql = "SELECT al.*, 
                       u.username, u.first_name, u.last_name, u.email,
                       CASE 
                           WHEN al.target_type = 'news' THEN n.title
                           WHEN al.target_type = 'event' THEN e.title
                           WHEN al.target_type = 'user' THEN CONCAT(tu.first_name, ' ', tu.last_name)
                           WHEN al.target_type = 'comment' THEN SUBSTRING(c.content, 1, 50)
                           ELSE NULL
                       END as target_title
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN news n ON al.target_type = 'news' AND al.target_id = n.id
                LEFT JOIN events e ON al.target_type = 'event' AND al.target_id = e.id
                LEFT JOIN users tu ON al.target_type = 'user' AND al.target_id = tu.id
                LEFT JOIN comments c ON al.target_type = 'comment' AND al.target_id = c.id
                {$whereClause}
                ORDER BY al.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        
        $activities = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $activities,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_previous' => $page > 1
            ]
        ];
    }

    private function getActivityDetails($activityId) {
        $sql = "SELECT al.*, 
                       u.username, u.first_name, u.last_name, u.email, u.profile_image,
                       CASE 
                           WHEN al.target_type = 'news' THEN n.title
                           WHEN al.target_type = 'event' THEN e.title
                           WHEN al.target_type = 'user' THEN CONCAT(tu.first_name, ' ', tu.last_name)
                           WHEN al.target_type = 'comment' THEN c.content
                           ELSE NULL
                       END as target_title
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN news n ON al.target_type = 'news' AND al.target_id = n.id
                LEFT JOIN events e ON al.target_type = 'event' AND al.target_id = e.id
                LEFT JOIN users tu ON al.target_type = 'user' AND al.target_id = tu.id
                LEFT JOIN comments c ON al.target_type = 'comment' AND al.target_id = c.id
                WHERE al.id = ?";
        
        return $this->db->fetch($sql, [$activityId]);
    }

    private function getAvailableActions() {
        $sql = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
        $results = $this->db->fetchAll($sql);
        return array_column($results, 'action');
    }

    private function getActiveUsers() {
        $sql = "SELECT DISTINCT u.id, u.username, u.first_name, u.last_name 
                FROM users u 
                INNER JOIN activity_logs al ON u.id = al.user_id 
                WHERE u.deleted_at IS NULL 
                ORDER BY u.username";
        return $this->db->fetchAll($sql);
    }

    private function getTargetTypes() {
        $sql = "SELECT DISTINCT target_type FROM activity_logs WHERE target_type IS NOT NULL ORDER BY target_type";
        $results = $this->db->fetchAll($sql);
        return array_column($results, 'target_type');
    }

    private function getActivityStats() {
        $sql = "SELECT 
                    COUNT(*) as total_activities,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_count,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_count,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_count
                FROM activity_logs";
        
        return $this->db->fetch($sql);
    }

    private function exportActivities($format, $filters) {
        // Build query with filters
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['action'])) {
            $whereConditions[] = "al.action = ?";
            $params[] = $filters['action'];
        }
        
        if ($filters['user_id'] > 0) {
            $whereConditions[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['target_type'])) {
            $whereConditions[] = "al.target_type = ?";
            $params[] = $filters['target_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = "(al.action LIKE ? OR al.target_type LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT al.id, al.action, al.target_type, al.target_id, 
                       u.username, u.email, al.ip_address, al.user_agent, al.created_at,
                       al.metadata
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                {$whereClause}
                ORDER BY al.created_at DESC
                LIMIT 10000"; // Limit export to prevent memory issues
        
        $activities = $this->db->fetchAll($sql, $params);
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "activities_export_{$timestamp}.{$format}";
        $filepath = __DIR__ . '/../../exports/';
        
        // Create exports directory if it doesn't exist
        if (!is_dir($filepath)) {
            mkdir($filepath, 0755, true);
        }
        
        $fullPath = $filepath . $filename;
        
        if ($format === 'csv') {
            $this->exportToCsv($activities, $fullPath);
        } else {
            throw new Exception('Unsupported export format');
        }
        
        return $filename;
    }

    private function exportToCsv($activities, $filepath) {
        $file = fopen($filepath, 'w');
        
        // Write header
        $headers = ['ID', 'Action', 'Target Type', 'Target ID', 'Username', 'Email', 'IP Address', 'User Agent', 'Created At', 'Metadata'];
        fputcsv($file, $headers);
        
        // Write data
        foreach ($activities as $activity) {
            fputcsv($file, [
                $activity['id'],
                $activity['action'],
                $activity['target_type'],
                $activity['target_id'],
                $activity['username'],
                $activity['email'],
                $activity['ip_address'],
                $activity['user_agent'],
                $activity['created_at'],
                $activity['metadata']
            ]);
        }
        
        fclose($file);
    }

    private function cleanupOldActivities($days) {
        $sql = "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->db->execute($sql, [$days]);
    }
}