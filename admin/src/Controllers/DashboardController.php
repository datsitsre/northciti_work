<?php
// admin/src/Controllers/DashboardController.php - Updated Dashboard Controller

require_once __DIR__ . '/../Helpers/LayoutHelper.php';

class DashboardController 
{
    public static function index()
    {
        // Check authentication
        if (!AuthController::isLoggedIn()) {
            redirect(Router::url('login'));
            return;
        }

        $user = AuthController::getCurrentUser();
        
        // Get dashboard statistics
        $stats = self::getDashboardStats();
        
        // Set page data
        LayoutHelper::setPageData(
            'Dashboard',
            'Overview of your platform',
            []
        );
        
        // Render the dashboard view
        LayoutHelper::render('dashboard/index', [
            'user' => $user,
            'stats' => $stats
        ]);
    }
    
    public static function getDashboardStats()
    {
        try {
            $db = Database::getInstance();
            
            // Users statistics
            $userStats = $db->fetch("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_month
                FROM users 
                WHERE deleted_at IS NULL
            ");
            
            // News statistics - Check if table exists first
            try {
                $newsStats = $db->fetch("
                    SELECT 
                        COUNT(*) as total_news,
                        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_news,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_news,
                        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as new_news_week
                    FROM news 
                    WHERE deleted_at IS NULL
                ");
            } catch (Exception $e) {
                $newsStats = [
                    'total_news' => 0,
                    'published_news' => 0,
                    'pending_news' => 0,
                    'new_news_week' => 0
                ];
            }
            
            // Events statistics - Check if table exists first
            try {
                $eventStats = $db->fetch("
                    SELECT 
                        COUNT(*) as total_events,
                        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_events,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_events,
                        SUM(CASE WHEN start_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events
                    FROM events 
                    WHERE deleted_at IS NULL
                ");
            } catch (Exception $e) {
                $eventStats = [
                    'total_events' => 0,
                    'published_events' => 0,
                    'pending_events' => 0,
                    'upcoming_events' => 0
                ];
            }
            
            // Comments statistics - Check if table exists first
            try {
                $commentStats = $db->fetch("
                    SELECT 
                        COUNT(*) as total_comments,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_comments,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_comments,
                        SUM(CASE WHEN is_flagged = 1 THEN 1 ELSE 0 END) as flagged_comments
                    FROM comments 
                    WHERE deleted_at IS NULL
                ");
            } catch (Exception $e) {
                $commentStats = [
                    'total_comments' => 0,
                    'approved_comments' => 0,
                    'pending_comments' => 0,
                    'flagged_comments' => 0
                ];
            }
            
            return [
                'users' => $userStats ?: [
                    'total_users' => 0,
                    'active_users' => 0,
                    'pending_users' => 0,
                    'new_users_month' => 0
                ],
                'news' => $newsStats,
                'events' => $eventStats,
                'comments' => $commentStats
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching dashboard stats: " . $e->getMessage());
            
            // Return default values if error occurs
            return [
                'users' => [
                    'total_users' => 0,
                    'active_users' => 0,
                    'pending_users' => 0,
                    'new_users_month' => 0
                ],
                'news' => [
                    'total_news' => 0,
                    'published_news' => 0,
                    'pending_news' => 0,
                    'new_news_week' => 0
                ],
                'events' => [
                    'total_events' => 0,
                    'published_events' => 0,
                    'pending_events' => 0,
                    'upcoming_events' => 0
                ],
                'comments' => [
                    'total_comments' => 0,
                    'approved_comments' => 0,
                    'pending_comments' => 0,
                    'flagged_comments' => 0
                ]
            ];
        }
    }
    
    public static function getRecentActivity()
    {
        try {
            $db = Database::getInstance();
            
            // Check if activity_logs table exists
            try {
                $activities = $db->fetchAll("
                    SELECT 
                        al.*,
                        u.first_name,
                        u.last_name,
                        u.email
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT 10
                ");
                
                return $activities;
            } catch (Exception $e) {
                // If activity_logs table doesn't exist, return empty array
                return [];
            }
            
        } catch (Exception $e) {
            error_log("Error fetching recent activity: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getSystemInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'mysql_version' => Database::getInstance()->getPdo()->query('SELECT VERSION()')->fetchColumn(),
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'disk_free_space' => disk_free_space('.'),
            'disk_total_space' => disk_total_space('.')
        ];
    }
}
