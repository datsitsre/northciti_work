<?php

// api/src/Controllers/Admin/ContentModerationController.php - Updated Content Moderation Controller

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\User;
use App\Models\News;
use App\Models\Event;
use App\Models\Comment;
use App\Models\Media;
use App\Services\EmailService;
use App\Services\CacheService;
use App\Services\ModerationService;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;

class ContentModerationController extends BaseController
{
    private User $userModel;
    private News $newsModel;
    private Event $eventModel;
    private Comment $commentModel;
    private Media $mediaModel;
    private EmailService $emailService;
    private CacheService $cache;
    private ModerationService $moderationService;

    public function __construct(
        Database $database,
        User $userModel,
        News $newsModel,
        Event $eventModel,
        Comment $commentModel,
        Media $mediaModel,
        EmailService $emailService,
        CacheService $cache,
        ModerationService $moderationService
    ) {
        parent::__construct();
        $this->db = $database;
        $this->userModel = $userModel;
        $this->newsModel = $newsModel;
        $this->eventModel = $eventModel;
        $this->commentModel = $commentModel;
        $this->mediaModel = $mediaModel;
        $this->emailService = $emailService;
        $this->moderationService = $moderationService;
        $this->cache = $cache;
    }

    /**
     * Get unified moderation queue - Updated for merged system
     */
    public function queue(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 20), 100);
        $contentType = $request->getQuery('type', 'all'); // all, news, events, comments, media
        $priority = $request->getQuery('priority');
        $status = $request->getQuery('status', 'pending');
        $flaggedOnly = $request->getQueryBool('flagged_only', false);

        $filters = [
            'priority' => $priority,
            'flagged_only' => $flaggedOnly
        ];

        try {
            $result = $this->getUnifiedModerationQueue($page, $perPage, $contentType, $status, $filters);
            
            return $this->paginatedResponse($result['data'], $result['pagination'], [
                'filters' => [
                    'type' => $contentType,
                    'priority' => $priority,
                    'status' => $status,
                    'flagged_only' => $flaggedOnly
                ],
                'statistics' => $this->getUnifiedModerationStatistics()
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch moderation queue: ' . $e->getMessage());
        }
    }

    /**
     * Moderate content - Updated for unified system
     */
    public function moderate(Request $request, string $type, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $contentId = (int)$id;
        $data = $request->getAllData();
        
        $validation = $this->validateModerationData($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $action = $data['action'];
        $reason = $data['reason'] ?? '';

        try {
            $result = $this->performModerationAction($type, $contentId, $action, $reason, $request->getUser()['id']);
            
            if ($result['success']) {
                // Update unified moderation queue
                $this->updateUnifiedModerationQueue($type, $contentId, $action, $request->getUser()['id']);

                // Send notification if needed
                if (in_array($action, ['approve', 'reject']) && !empty($result['author_email'])) {
                    $this->sendModerationNotification($result['author_email'], $type, $action, $reason, $result['content_title']);
                }

                // Clear caches
                $this->clearModerationCaches($type);

                // Log activity
                $this->logActivity($request->getUser()['id'], 'content_moderated', $type, $contentId, [
                    'action' => $action,
                    'reason' => $reason
                ]);

                return $this->successResponse($result, 'Content moderated successfully');
            } else {
                return $this->errorResponse($result['message'] ?? 'Moderation failed');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Moderation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get unified moderation statistics
     */
    public function statistics(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $timeframe = $request->getQuery('timeframe', '7d');
        
        $cacheKey = $this->cache->generateKey('unified_moderation_stats', $timeframe);
        
        try {
            $stats = $this->cache->remember($cacheKey, function() use ($timeframe) {
                return [
                    'overview' => $this->getUnifiedModerationStatistics(),
                    'timeline' => $this->getModerationTimeline($timeframe),
                    'moderator_activity' => $this->getModeratorActivity($timeframe),
                    'content_breakdown' => $this->getContentTypeBreakdown(),
                    'flag_statistics' => $this->getFlagStatistics($timeframe),
                    'response_times' => $this->getResponseTimeStatistics($timeframe)
                ];
            }, 300); // Cache for 5 minutes

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get unified moderation queue data
     */
    private function getUnifiedModerationQueue(int $page, int $perPage, string $contentType, string $status, array $filters): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = ["mq.status = ?"];
        $params = [$status];

        // Filter by content type
        if ($contentType !== 'all') {
            $whereConditions[] = "mq.content_type = ?";
            $params[] = $contentType;
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $whereConditions[] = "mq.priority = ?";
            $params[] = $filters['priority'];
        }

        // Filter flagged only
        if ($filters['flagged_only']) {
            $whereConditions[] = "(c.is_flagged = 1 OR n.is_flagged = 1 OR e.is_flagged = 1 OR m.is_flagged = 1)";
        }

        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM moderation_queue mq 
                     LEFT JOIN comments c ON mq.content_type = 'comment' AND mq.content_id = c.id
                     LEFT JOIN news n ON mq.content_type = 'news' AND mq.content_id = n.id
                     LEFT JOIN events e ON mq.content_type = 'event' AND mq.content_id = e.id
                     LEFT JOIN media m ON mq.content_type = 'media' AND mq.content_id = m.id
                     {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];

        // Get queue items with enhanced data
        $sql = "SELECT 
                    mq.*,
                    u.username, u.first_name, u.last_name, u.email, u.role, u.profile_image,
                    CASE 
                        WHEN mq.content_type = 'news' THEN n.title
                        WHEN mq.content_type = 'event' THEN e.title
                        WHEN mq.content_type = 'comment' THEN SUBSTRING(c.content, 1, 100)
                        WHEN mq.content_type = 'media' THEN m.original_filename
                        ELSE 'Unknown Content'
                    END as content_title,
                    CASE 
                        WHEN mq.content_type = 'news' THEN n.status
                        WHEN mq.content_type = 'event' THEN e.status
                        WHEN mq.content_type = 'comment' THEN c.status
                        WHEN mq.content_type = 'media' THEN CASE WHEN m.is_approved = 1 THEN 'approved' ELSE 'pending' END
                        ELSE 'unknown'
                    END as content_status,
                    CASE 
                        WHEN mq.content_type = 'comment' THEN c.is_flagged
                        WHEN mq.content_type = 'news' THEN n.is_flagged
                        WHEN mq.content_type = 'event' THEN e.is_flagged
                        WHEN mq.content_type = 'media' THEN m.is_flagged
                        ELSE 0
                    END as is_flagged,
                    CASE 
                        WHEN mq.content_type = 'comment' THEN c.flag_count
                        ELSE 0
                    END as flag_count,
                    CASE 
                        WHEN mq.content_type = 'comment' THEN c.upvotes
                        ELSE 0
                    END as upvotes,
                    CASE 
                        WHEN mq.content_type = 'comment' THEN c.downvotes
                        ELSE 0
                    END as downvotes,
                    CASE 
                        WHEN mq.content_type = 'comment' THEN c.created_at
                        WHEN mq.content_type = 'news' THEN n.created_at
                        WHEN mq.content_type = 'event' THEN e.created_at
                        WHEN mq.content_type = 'media' THEN m.created_at
                        ELSE mq.created_at
                    END as content_created_at
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
                    CASE 
                        WHEN mq.content_type = 'comment' THEN c.is_flagged
                        WHEN mq.content_type = 'news' THEN n.is_flagged
                        WHEN mq.content_type = 'event' THEN e.is_flagged
                        WHEN mq.content_type = 'media' THEN m.is_flagged
                        ELSE 0
                    END DESC,
                    mq.moderation_score DESC,
                    mq.created_at ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $results = $this->db->fetchAll($sql, $params);

        // Parse JSON fields and enrich data
        foreach ($results as &$item) {
            if ($item['flags']) {
                $item['flags'] = json_decode($item['flags'], true) ?: [];
            }
            if ($item['reasons']) {
                $item['reasons'] = json_decode($item['reasons'], true) ?: [];
            }
        }

        return [
            'data' => $results,
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

    /**
     * Get unified moderation statistics
     */
    private function getUnifiedModerationStatistics(): array
    {
        $sql = "SELECT 
                    -- Queue statistics
                    (SELECT COUNT(*) FROM moderation_queue WHERE status = 'pending') as pending_items,
                    (SELECT COUNT(*) FROM moderation_queue WHERE status = 'in_review') as in_review_items,
                    (SELECT COUNT(*) FROM moderation_queue WHERE status = 'approved') as approved_items,
                    (SELECT COUNT(*) FROM moderation_queue WHERE status = 'rejected') as rejected_items,
                    
                    -- Content type breakdown
                    (SELECT COUNT(*) FROM moderation_queue WHERE content_type = 'comment' AND status = 'pending') as pending_comments,
                    (SELECT COUNT(*) FROM moderation_queue WHERE content_type = 'news' AND status = 'pending') as pending_news,
                    (SELECT COUNT(*) FROM moderation_queue WHERE content_type = 'event' AND status = 'pending') as pending_events,
                    (SELECT COUNT(*) FROM moderation_queue WHERE content_type = 'media' AND status = 'pending') as pending_media,
                    
                    -- Flag statistics
                    (SELECT COUNT(*) FROM content_flags WHERE status = 'pending') as pending_flags,
                    (SELECT COUNT(*) FROM content_flags WHERE status = 'resolved') as resolved_flags,
                    
                    -- Today's activity
                    (SELECT COUNT(*) FROM moderation_queue WHERE DATE(reviewed_at) = CURDATE() AND reviewed_at IS NOT NULL) as today_reviews,
                    
                    -- Calculate approval rate
                    (SELECT 
                        CASE WHEN COUNT(*) > 0 
                        THEN ROUND((COUNT(CASE WHEN status = 'approved' THEN 1 END) * 100.0 / COUNT(*)), 1)
                        ELSE 0 
                        END
                     FROM moderation_queue 
                     WHERE reviewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ) as approval_rate,
                    
                    -- Average response time in hours
                    (SELECT 
                        ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, reviewed_at)) / 60, 1)
                     FROM moderation_queue 
                     WHERE reviewed_at IS NOT NULL AND reviewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ) as avg_response_time_hours";

        $result = $this->db->fetch($sql);

        // Add computed metrics
        $result['pending_count'] = $result['pending_items'];
        $result['flagged_count'] = $result['pending_flags'];
        $result['avg_review_time'] = $result['avg_response_time_hours'] ?? 0;

        return $result ?: [];
    }

    /**
     * Get content type breakdown for statistics
     */
    private function getContentTypeBreakdown(): array
    {
        $sql = "SELECT 
                    content_type,
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
                    ROUND(AVG(CASE WHEN reviewed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, reviewed_at) END) / 60, 1) as avg_response_hours
                FROM moderation_queue 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY content_type
                ORDER BY total DESC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get moderation timeline data
     */
    private function getModerationTimeline(string $timeframe): array
    {
        $days = match($timeframe) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7
        };

        $sql = "SELECT 
                    DATE(created_at) as date,
                    content_type,
                    COUNT(*) as total_items,
                    COUNT(CASE WHEN status IN ('approved', 'rejected') THEN 1 END) as processed,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
                FROM moderation_queue 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at), content_type
                ORDER BY date DESC, content_type";

        return $this->db->fetchAll($sql, [$days]);
    }

    /**
     * Get moderator activity statistics
     */
    private function getModeratorActivity(string $timeframe): array
    {
        $days = match($timeframe) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7
        };

        $sql = "SELECT 
                    u.username,
                    u.first_name,
                    u.last_name,
                    COUNT(*) as total_actions,
                    COUNT(CASE WHEN mq.status = 'approved' THEN 1 END) as approvals,
                    COUNT(CASE WHEN mq.status = 'rejected' THEN 1 END) as rejections,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, mq.created_at, mq.reviewed_at)) / 60, 1) as avg_response_hours,
                    COUNT(DISTINCT mq.content_type) as content_types_handled
                FROM moderation_queue mq
                JOIN users u ON mq.assigned_to = u.id
                WHERE mq.reviewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY u.id, u.username, u.first_name, u.last_name
                ORDER BY total_actions DESC";

        return $this->db->fetchAll($sql, [$days]);
    }

    /**
     * Get flag statistics
     */
    private function getFlagStatistics(string $timeframe): array
    {
        $days = match($timeframe) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7
        };

        $sql = "SELECT 
                    cf.content_type,
                    cf.flag_type,
                    COUNT(*) as flag_count,
                    COUNT(CASE WHEN cf.status = 'resolved' THEN 1 END) as resolved_count,
                    COUNT(CASE WHEN cf.status = 'pending' THEN 1 END) as pending_count
                FROM content_flags cf
                WHERE cf.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY cf.content_type, cf.flag_type
                ORDER BY flag_count DESC";

        return $this->db->fetchAll($sql, [$days]);
    }

    /**
     * Get response time statistics
     */
    private function getResponseTimeStatistics(string $timeframe): array
    {
        $days = match($timeframe) {
            '24h' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7
        };

        $sql = "SELECT 
                    content_type,
                    COUNT(*) as total_reviewed,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, reviewed_at)) / 60, 1) as avg_hours,
                    ROUND(MIN(TIMESTAMPDIFF(MINUTE, created_at, reviewed_at)) / 60, 1) as min_hours,
                    ROUND(MAX(TIMESTAMPDIFF(MINUTE, created_at, reviewed_at)) / 60, 1) as max_hours,
                    COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, reviewed_at) <= 24 THEN 1 END) as within_24h,
                    COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, reviewed_at) > 24 THEN 1 END) as over_24h
                FROM moderation_queue 
                WHERE reviewed_at IS NOT NULL 
                AND reviewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY content_type
                ORDER BY avg_hours ASC";

        return $this->db->fetchAll($sql, [$days]);
    }

    /**
     * Update unified moderation queue status
     */
    private function updateUnifiedModerationQueue(string $type, int $contentId, string $action, int $moderatorId): void
    {
        $newStatus = match($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'flag' => 'flagged',
            'hide' => 'hidden',
            default => 'processed'
        };

        $sql = "UPDATE moderation_queue 
                SET status = ?, assigned_to = ?, reviewed_at = NOW(), updated_at = NOW()
                WHERE content_type = ? AND content_id = ? AND status = 'pending'";
        
        $this->db->execute($sql, [$newStatus, $moderatorId, $type, $contentId]);
    }

    // ... (keep other existing methods like performModerationAction, moderateNews, etc.)
    // ... (keep helper methods like validateModerationData, sendModerationNotification, etc.)

    /**
     * Export unified moderation data
     */
    public function export(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $format = $request->getQuery('format', 'csv');
        $dateFrom = $request->getQuery('date_from');
        $dateTo = $request->getQuery('date_to');
        $contentType = $request->getQuery('type', 'all');

        if (!in_array($format, ['csv'])) {
            return $this->errorResponse('Invalid format. Supported: csv');
        }

        try {
            $filename = $this->exportUnifiedModerationData($format, $dateFrom, $dateTo, $contentType);
            
            // Log activity
            $this->logActivity($request->getUser()['id'], 'moderation_exported', 'system', 0, [
                'format' => $format,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'content_type' => $contentType
            ]);

            return $this->successResponse([
                'download_url' => '/api/admin/downloads/' . $filename,
                'filename' => $filename
            ], 'Export completed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export unified moderation data to CSV
     */
    private function exportUnifiedModerationData(string $format, ?string $dateFrom, ?string $dateTo, string $contentType): string
    {
        // Build export query for unified moderation queue
        $whereConditions = [];
        $params = [];

        if ($dateFrom) {
            $whereConditions[] = "mq.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo) {
            $whereConditions[] = "mq.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        if ($contentType !== 'all') {
            $whereConditions[] = "mq.content_type = ?";
            $params[] = $contentType;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT 
                    mq.id,
                    mq.content_type,
                    mq.content_id,
                    mq.priority,
                    mq.status,
                    mq.moderation_score,
                    mq.auto_moderated,
                    mq.flags,
                    mq.reasons,
                    mq.created_at,
                    mq.reviewed_at,
                    mq.updated_at,
                    u1.username as author_username,
                    u1.email as author_email,
                    u2.username as reviewer_username,
                    CASE 
                        WHEN mq.content_type = 'news' THEN n.title
                        WHEN mq.content_type = 'event' THEN e.title
                        WHEN mq.content_type = 'comment' THEN SUBSTRING(c.content, 1, 100)
                        WHEN mq.content_type = 'media' THEN m.original_filename
                        ELSE 'Unknown Content'
                    END as content_title
                FROM moderation_queue mq
                LEFT JOIN users u1 ON mq.author_id = u1.id
                LEFT JOIN users u2 ON mq.assigned_to = u2.id
                LEFT JOIN news n ON mq.content_type = 'news' AND mq.content_id = n.id
                LEFT JOIN events e ON mq.content_type = 'event' AND mq.content_id = e.id
                LEFT JOIN comments c ON mq.content_type = 'comment' AND mq.content_id = c.id
                LEFT JOIN media m ON mq.content_type = 'media' AND mq.content_id = m.id
                {$whereClause}
                ORDER BY mq.created_at DESC";

        $data = $this->db->fetchAll($sql, $params);

        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "unified_moderation_export_{$timestamp}.{$format}";
        $filepath = dirname(__DIR__, 3) . '/storage/exports/' . $filename;

        // Create exports directory if it doesn't exist
        $exportDir = dirname($filepath);
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Export data
        if ($format === 'csv') {
            $this->exportToCsv($data, $filepath);
        }

        return $filename;
    }

    private function exportToCsv(array $data, string $filepath): void
    {
        if (empty($data)) {
            file_put_contents($filepath, '');
            return;
        }

        $file = fopen($filepath, 'w');

        // Write header
        $headers = [
            'ID', 'Content Type', 'Content ID', 'Content Title', 'Priority', 'Status', 
            'Moderation Score', 'Auto Moderated', 'Flags', 'Reasons',
            'Author', 'Author Email', 'Reviewer', 'Created At', 'Reviewed At', 'Updated At'
        ];
        fputcsv($file, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($file, [
                $row['id'],
                $row['content_type'],
                $row['content_id'],
                $row['content_title'],
                $row['priority'],
                $row['status'],
                $row['moderation_score'],
                $row['auto_moderated'] ? 'Yes' : 'No',
                $row['flags'],
                $row['reasons'],
                $row['author_username'],
                $row['author_email'],
                $row['reviewer_username'],
                $row['created_at'],
                $row['reviewed_at'],
                $row['updated_at']
            ]);
        }

        fclose($file);
    }

    // Keep existing helper methods...
    private function performModerationAction(string $type, int $contentId, string $action, string $reason, int $moderatorId): array
    {
        $result = ['success' => false, 'message' => 'Unknown error'];
        
        try {
            switch ($type) {
                case 'news':
                    $result = $this->moderateNews($contentId, $action, $reason, $moderatorId);
                    break;
                case 'event':
                    $result = $this->moderateEvent($contentId, $action, $reason, $moderatorId);
                    break;
                case 'comment':
                    $result = $this->moderateCommentAction($contentId, $action, $reason, $moderatorId);
                    break;
                case 'media':
                    $result = $this->moderateMedia($contentId, $action, $reason, $moderatorId);
                    break;
                default:
                    $result = ['success' => false, 'message' => 'Invalid content type'];
            }

            return $result;

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function moderateNews(int $newsId, string $action, string $reason, int $moderatorId): array
    {
        $news = $this->newsModel->find($newsId);
        if (!$news) {
            return ['success' => false, 'message' => 'News article not found'];
        }

        $newStatus = match($action) {
            'approve' => 'published',
            'reject' => 'rejected',
            'hide' => 'archived',
            'flag' => 'pending',
            default => null
        };

        if (!$newStatus) {
            return ['success' => false, 'message' => 'Invalid action for news'];
        }

        $updateData = [
            'status' => $newStatus,
            'approved_by' => $moderatorId,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        $updated = $this->newsModel->update($newsId, $updateData);
        
        if ($updated) {
            return [
                'success' => true,
                'message' => "News article {$action}d successfully",
                'author_email' => $news['author_email'] ?? '',
                'content_title' => $news['title']
            ];
        }

        return ['success' => false, 'message' => 'Failed to update news status'];
    }

    private function moderateEvent(int $eventId, string $action, string $reason, int $moderatorId): array
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return ['success' => false, 'message' => 'Event not found'];
        }

        $newStatus = match($action) {
            'approve' => 'published',
            'reject' => 'rejected',
            'hide' => 'archived',
            'flag' => 'pending',
            default => null
        };

        if (!$newStatus) {
            return ['success' => false, 'message' => 'Invalid action for event'];
        }

        $updateData = [
            'status' => $newStatus,
            'approved_by' => $moderatorId,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        $updated = $this->eventModel->update($eventId, $updateData);
        
        if ($updated) {
            return [
                'success' => true,
                'message' => "Event {$action}d successfully",
                'author_email' => $event['organizer_email'] ?? '',
                'content_title' => $event['title']
            ];
        }

        return ['success' => false, 'message' => 'Failed to update event status'];
    }

    private function moderateCommentAction(int $commentId, string $action, string $reason, int $moderatorId): array
    {
        $comment = $this->commentModel->find($commentId);
        if (!$comment) {
            return ['success' => false, 'message' => 'Comment not found'];
        }

        $success = match($action) {
            'approve' => $this->commentModel->approveComment($commentId),
            'reject' => $this->commentModel->rejectComment($commentId, $moderatorId),
            'hide' => $this->commentModel->update($commentId, ['status' => 'hidden']),
            'delete' => $this->commentModel->delete($commentId),
            default => false
        };

        if ($success) {
            return [
                'success' => true,
                'message' => "Comment {$action}d successfully",
                'author_email' => $comment['author_email'] ?? '',
                'content_title' => 'Comment: ' . substr($comment['content'], 0, 50) . '...'
            ];
        }

        return ['success' => false, 'message' => 'Failed to moderate comment'];
    }

    private function moderateMedia(int $mediaId, string $action, string $reason, int $moderatorId): array
    {
        $media = $this->mediaModel->find($mediaId);
        if (!$media) {
            return ['success' => false, 'message' => 'Media not found'];
        }

        $success = match($action) {
            'approve' => $this->mediaModel->update($mediaId, ['is_approved' => 1, 'moderated_by' => $moderatorId, 'moderated_at' => date('Y-m-d H:i:s')]),
            'reject' => $this->mediaModel->update($mediaId, ['is_rejected' => 1, 'moderated_by' => $moderatorId, 'moderated_at' => date('Y-m-d H:i:s')]),
            'hide' => $this->mediaModel->update($mediaId, ['is_public' => 0]),
            'delete' => $this->mediaModel->delete($mediaId),
            default => false
        };

        if ($success) {
            return [
                'success' => true,
                'message' => "Media {$action}d successfully",
                'author_email' => '',
                'content_title' => $media['original_filename']
            ];
        }

        return ['success' => false, 'message' => 'Failed to moderate media'];
    }

    private function validateModerationData(array $data): array
    {
        $errors = [];

        if (empty($data['action'])) {
            $errors['action'] = 'Action is required';
        } elseif (!in_array($data['action'], ['approve', 'reject', 'flag', 'hide', 'delete'])) {
            $errors['action'] = 'Invalid action';
        }

        if (isset($data['reason']) && strlen($data['reason']) > 1000) {
            $errors['reason'] = 'Reason must not exceed 1000 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function clearModerationCaches(string $contentType): void
    {
        $patterns = [
            'unified_moderation_*',
            'moderation_*',
            $contentType . '_*',
            'stats_*'
        ];

        foreach ($patterns as $pattern) {
            $this->cache->deletePattern($pattern);
        }
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
    {
        $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $this->db->execute($sql, [
            $userId,
            $action,
            $targetType,
            $targetId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($metadata)
        ]);
    }

    private function sendModerationNotification(string $email, string $contentType, string $action, string $reason, string $contentTitle): void
    {
        $subject = ucfirst($contentType) . ' ' . ucfirst($action) . 'd';
        $actionText = $action === 'approve' ? 'approved and published' : $action . 'd';
        
        $body = "
        <html>
        <body>
            <h2>Content Moderation Update</h2>
            <p>Your {$contentType} '{$contentTitle}' has been {$actionText}.</p>
            " . ($reason ? "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>" : "") . "
            <p>Thank you for your contribution to our platform.</p>
            <p>Best regards,<br>The Moderation Team</p>
        </body>
        </html>";

        $emailData = [
            'to_email' => $email,
            'subject' => $subject,
            'body_html' => $body,
            'priority' => 'normal'
        ];

        $this->emailService->queue($emailData);
    }
}