<?php

// admin/src/Controllers/ModerationController.php - Updated Admin Moderation Controller

class ModerationController
{
    private $db;
    private $user;
    private $mediaModel;
    private $userModel;

    public function __construct()
    {
        $this->mediaModel = new MediaModel();
        $this->userModel = new UserModel();
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
        
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRole('super_admin');
    }

    /**
     * Main moderation queue page - Updated for unified system
     */
    public function index()
    {
        try {
            // Get filters from query parameters
            $page = (int)($_GET['page'] ?? 1);
            $perPage = min((int)($_GET['per_page'] ?? 20), 100);
            $status = $_GET['status'] ?? 'pending';
            $type = $_GET['type'] ?? 'all'; // Updated to support all content types
            $search = $_GET['search'] ?? '';
            $priority = $_GET['priority'] ?? '';
            $flaggedOnly = isset($_GET['flagged_only']) ? (bool)$_GET['flagged_only'] : false;

            // Build API request parameters for unified moderation queue
            $params = [
                'page' => $page,
                'per_page' => $perPage,
                'status' => $status,
                'type' => $type,
                'flagged_only' => $flaggedOnly ? '1' : '0'
            ];

            if ($search) $params['search'] = $search;
            if ($priority) $params['priority'] = $priority;

            $queueData = [];
            $pagination = [];
            $stats = [];

            try {
                // Fetch unified moderation queue from API
                $queueResponse = ApiClient::get('/admin/moderation/queue', $params);
                $queueData = $queueResponse['data'] ?? [];
                $pagination = $queueResponse['meta']['pagination'] ?? [];
                $stats = $queueResponse['meta']['statistics'] ?? [];

            } catch (\Exception $e) {
                error_log("API Error in ModerationController::index: " . $e->getMessage());
                
                // Fallback to local unified data
                $queueData = $this->getLocalUnifiedModerationQueue($params);
                $stats = $this->getLocalUnifiedModerationStats();
                
                LayoutHelper::addFlashMessage('Using local data. API connection failed: ' . $e->getMessage(), 'warning');
            }

            // Set page data
            LayoutHelper::setPageData(
                'Unified Content Moderation',
                'Review and moderate all types of user-submitted content',
                []
            );
            
            // Render the unified moderation view
            LayoutHelper::render('moderation/index', [
                'title' => 'Unified Content Moderation',
                'queue_items' => $queueData,
                'pagination' => $pagination,
                'stats' => $stats,
                'filters' => [
                    'status' => $status,
                    'type' => $type,
                    'search' => $search,
                    'priority' => $priority,
                    'flagged_only' => $flaggedOnly
                ],
                'current_page' => $page,
                'per_page' => $perPage,
                'has_api_access' => ApiClient::isAuthenticated()
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'Failed to load unified moderation queue');
        }
    }

    /**
     * Show detailed content for moderation - Updated for all content types
     */
    public function show()
    {
        $contentType = $_GET['type'] ?? '';
        $contentId = (int)($_GET['id'] ?? 0);
        
        if (!$contentId || !in_array($contentType, ['news', 'event', 'comment', 'media'])) {
            $this->redirect('/admin/moderation', 'error', 'Invalid content type or ID');
            return;
        }

        try {
            $content = null;
            $moderationHistory = [];
            $flagHistory = [];

            if (ApiClient::isAuthenticated()) {
                try {
                    // Get detailed content info for moderation from unified system
                    $response = ApiClient::get("/admin/moderation/{$contentType}/{$contentId}");
                    $content = $response['data'] ?? null;
                    $moderationHistory = $response['data']['moderation_history'] ?? [];
                    $flagHistory = $response['data']['flag_history'] ?? [];
                } catch (\Exception $e) {
                    error_log("API Error in ModerationController::show: " . $e->getMessage());
                    $content = $this->getLocalContentById($contentType, $contentId);
                }
            } else {
                $content = $this->getLocalContentById($contentType, $contentId);
            }

            if (!$content) {
                $this->redirect('/admin/moderation', 'error', 'Content not found');
                return;
            }

            // Determine content title based on type
            $contentTitle = match($contentType) {
                'news' => $content['title'] ?? 'News Article',
                'event' => $content['title'] ?? 'Event',
                'comment' => 'Comment: ' . substr($content['content'] ?? '', 0, 50) . '...',
                'media' => $content['original_filename'] ?? 'Media File',
                default => 'Unknown Content'
            };

            // Set page data
            LayoutHelper::setPageData(
                'Review Content - ' . $contentTitle,
                'Review and moderate all types of user-submitted content',
                []
            );

            LayoutHelper::render('moderation/show', [
                'title' => 'Review Content - ' . $contentTitle,
                'content' => $content,
                'content_type' => $contentType,
                'content_id' => $contentId,
                'moderation_history' => $moderationHistory,
                'flag_history' => $flagHistory,
                'has_api_access' => ApiClient::isAuthenticated()
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'Failed to load content for review');
        }
    }

    /**
     * Moderate content - Updated for unified system
     */
    public function moderate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $contentType = $_POST['type'] ?? '';
            $contentId = (int)($_POST['id'] ?? 0);
            $action = $_POST['action'] ?? '';
            $reason = $_POST['reason'] ?? '';

            if (!$contentId || !in_array($contentType, ['news', 'event', 'comment', 'media']) || 
                !in_array($action, ['approve', 'reject', 'flag', 'hide', 'delete'])) {
                throw new \Exception('Invalid parameters');
            }

            // Perform moderation action via unified API
            $response = ApiClient::put("/admin/moderation/content/{$contentType}/{$contentId}", [
                'action' => $action,
                'reason' => $reason
            ]);

            if (isset($response['success']) && $response['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => ucfirst($action) . ' action completed successfully',
                    'action' => $action,
                    'content_type' => $contentType,
                    'content_id' => $contentId
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Moderation action failed');
            }

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Bulk moderate content - Updated for unified system
     */
    public function bulkModerate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                throw new \Exception('Invalid security token');
            }

            $items = $_POST['items'] ?? [];
            $action = $_POST['action'] ?? '';
            $reason = $_POST['reason'] ?? '';

            if (empty($items) || !in_array($action, ['approve', 'reject', 'flag', 'hide', 'delete'])) {
                throw new \Exception('Invalid parameters');
            }

            // Group items by content type for efficient processing
            $groupedItems = [];
            foreach ($items as $item) {
                $type = $item['type'] ?? '';
                $id = (int)($item['id'] ?? 0);
                
                if ($type && $id && in_array($type, ['news', 'event', 'comment', 'media'])) {
                    if (!isset($groupedItems[$type])) {
                        $groupedItems[$type] = [];
                    }
                    $groupedItems[$type][] = $id;
                }
            }

            // Perform bulk moderation action via unified API
            $response = ApiClient::post('/northcity/api/admin/moderation/bulk', [
                'items' => $groupedItems,
                'action' => $action,
                'reason' => $reason
            ]);

            if (isset($response['success']) && $response['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $response['message'] ?? 'Bulk moderation completed',
                    'processed' => $response['data']['processed'] ?? count($items),
                    'successful' => $response['data']['successful'] ?? 0,
                    'failed' => $response['data']['failed'] ?? 0,
                    'errors' => $response['data']['errors'] ?? []
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Bulk moderation failed');
            }

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Moderation statistics - Updated for unified system
     */
    public function statistics()
    {
        try {
            $timeframe = $_GET['timeframe'] ?? '7d';
            
            $stats = [];
            if (ApiClient::isAuthenticated()) {
                try {
                    $response = ApiClient::get('/admin/moderation/statistics', [
                        'timeframe' => $timeframe
                    ]);
                    $stats = $response['data'] ?? [];
                } catch (\Exception $e) {
                    error_log("API Error getting unified moderation stats: " . $e->getMessage());
                    $stats = $this->getLocalUnifiedModerationStats();
                }
            } else {
                $stats = $this->getLocalUnifiedModerationStats();
            }

            // Set page data
            LayoutHelper::setPageData(
                'Unified Content Moderation Statistics',
                'Review and moderate all types of user-submitted content',
                []
            );
            
            // Render the unified moderation view
            LayoutHelper::render('moderation/statistics', [
                'title' => 'Unified Moderation Statistics',
                'stats' => $stats,
                'timeframe' => $timeframe,
                'has_api_access' => ApiClient::isAuthenticated()
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'Failed to load unified moderation statistics');
        }
    }

    /**
     * Export unified moderation report
     */
    public function exportReport()
    {
        try {
            $format = $_GET['format'] ?? 'csv';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            $contentType = $_GET['content_type'] ?? 'all';

            if (ApiClient::isAuthenticated()) {
                $response = ApiClient::get('/northcity/api/admin/moderation/export', [
                    'format' => $format,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'type' => $contentType
                ]);

                if (isset($response['success']) && $response['success']) {
                    $this->redirect($response['data']['download_url'], 'success', 'Unified moderation report generated successfully');
                } else {
                    throw new \Exception($response['message'] ?? 'Export failed');
                }
            } else {
                throw new \Exception('API access required for export functionality');
            }

        } catch (\Exception $e) {
            $this->redirect('/admin/moderation/statistics', 'error', $e->getMessage());
        }
    }

    // Helper methods for unified local fallback
    private function getLocalUnifiedModerationQueue(array $params): array
    {
        try {
            // Query the unified moderation_queue table
            $page = $params['page'] ?? 1;
            $perPage = $params['per_page'] ?? 20;
            $offset = ($page - 1) * $perPage;
            
            $whereConditions = ["mq.status = ?"];
            $queryParams = [$params['status'] ?? 'pending'];

            // Filter by content type
            if (isset($params['type']) && $params['type'] !== 'all') {
                $whereConditions[] = "mq.content_type = ?";
                $queryParams[] = $params['type'];
            }

            // Filter by priority
            if (!empty($params['priority'])) {
                $whereConditions[] = "mq.priority = ?";
                $queryParams[] = $params['priority'];
            }

            // Filter flagged only
            if (!empty($params['flagged_only'])) {
                $whereConditions[] = "(c.is_flagged = 1 OR n.is_flagged = 1 OR e.is_flagged = 1 OR m.is_flagged = 1)";
            }

            $whereClause = "WHERE " . implode(" AND ", $whereConditions);

            $sql = "SELECT 
                        mq.*,
                        u.username, u.first_name, u.last_name, u.email, u.profile_image,
                        CASE 
                            WHEN mq.content_type = 'news' THEN n.title
                            WHEN mq.content_type = 'event' THEN e.title
                            WHEN mq.content_type = 'comment' THEN SUBSTRING(c.content, 1, 100)
                            WHEN mq.content_type = 'media' THEN m.original_filename
                            ELSE 'Unknown Content'
                        END as content_title,
                        CASE 
                            WHEN mq.content_type = 'comment' THEN c.is_flagged
                            WHEN mq.content_type = 'news' THEN n.is_flagged
                            WHEN mq.content_type = 'event' THEN e.is_flagged
                            WHEN mq.content_type = 'media' THEN m.is_flagged
                            ELSE 0
                        END as is_flagged
                    FROM moderation_queue mq
                    LEFT JOIN users u ON mq.author_id = u.id
                    LEFT JOIN comments c ON mq.content_type = 'comment' AND mq.content_id = c.id
                    LEFT JOIN news n ON mq.content_type = 'news' AND mq.content_id = n.id
                    LEFT JOIN events e ON mq.content_type = 'event' AND mq.content_id = e.id
                    LEFT JOIN media m ON mq.content_type = 'media' AND mq.content_id = m.id
                    {$whereClause}
                    ORDER BY 
                        CASE mq.priority 
                            WHEN 'urgent' THEN 1 
                            WHEN 'high' THEN 2 
                            WHEN 'medium' THEN 3 
                            WHEN 'low' THEN 4 
                        END,
                        mq.created_at ASC
                    LIMIT {$perPage} OFFSET {$offset}";

            return $this->db->fetchAll($sql, $queryParams) ?: [];

        } catch (\Exception $e) {
            error_log("Local unified moderation queue error: " . $e->getMessage());
            return [];
        }
    }

    private function getLocalUnifiedModerationStats(): array
    {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM moderation_queue WHERE status = 'pending') as pending_count,
                        (SELECT COUNT(*) FROM moderation_queue WHERE status = 'approved') as approved_count,
                        (SELECT COUNT(*) FROM moderation_queue WHERE status = 'rejected') as rejected_count,
                        (SELECT COUNT(*) FROM content_flags WHERE status = 'pending') as flagged_count,
                        (SELECT COUNT(*) FROM moderation_queue WHERE DATE(reviewed_at) = CURDATE()) as today_reviews,
                        (SELECT 
                            CASE WHEN COUNT(*) > 0 
                            THEN ROUND((COUNT(CASE WHEN status = 'approved' THEN 1 END) * 100.0 / COUNT(*)), 1)
                            ELSE 0 
                            END
                         FROM moderation_queue 
                         WHERE reviewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ) as approval_rate";

            return $this->db->fetch($sql) ?: [];

        } catch (\Exception $e) {
            error_log("Local unified moderation stats error: " . $e->getMessage());
            return [];
        }
    }

    private function getLocalContentById(string $contentType, int $contentId): ?array
    {
        try {
            $table = match($contentType) {
                'news' => 'news',
                'event' => 'events',
                'comment' => 'comments',
                'media' => 'media',
                default => null
            };

            if (!$table) {
                return null;
            }

            $sql = "SELECT * FROM {$table} WHERE id = ?";
            return $this->db->fetch($sql, [$contentId]);

        } catch (\Exception $e) {
            error_log("Local content by ID error: " . $e->getMessage());
            return null;
        }
    }

    // Helper methods (keep existing ones)
    private function requireRole(string $role): void
    {
        if (!$this->user || $this->user['role'] !== $role) {
            throw new \Exception('Insufficient permissions');
        }
    }

    private function redirect(string $url, string $type = '', string $message = ''): void
    {
        if ($message) {
            LayoutHelper::addFlashMessage($message, $type);
        }
        header('Location: ' . $url);
        exit;
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function validateCSRF(): bool
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        return validateCSRFToken($token);
    }

    private function handleError(\Exception $e, string $message = ''): void
    {
        error_log('ModerationController Error: ' . $e->getMessage());
        $this->redirect('/admin/moderation', 'error', $message ?: $e->getMessage());
    }
}