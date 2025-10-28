<?php

// api/src/Controllers/Admin/MediaModerationController.php - Updated Media Moderation Controller

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Media;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\CacheService;
use App\Services\ModerationService;
use App\Services\EmailService;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;

class MediaModerationController extends BaseController
{
    private Media $mediaModel;
    private User $userModel;
    private FileUploadService $fileUploadService;
    private CacheService $cache;
    private ModerationService $moderationService;
    private EmailService $emailService;

    public function __construct(
        Database $database,
        Media $mediaModel,
        User $userModel,
        FileUploadService $fileUploadService,
        CacheService $cache,
        ModerationService $moderationService,
        EmailService $emailService
    ) {
        parent::__construct();
        $this->db = $database;
        $this->mediaModel = $mediaModel;
        $this->userModel = $userModel;
        $this->fileUploadService = $fileUploadService;
        $this->cache = $cache;
        $this->moderationService = $moderationService;
        $this->emailService = $emailService;
    }

    /**
     * Get media moderation queue from unified system
     */
    public function index(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 50), 200);
        $type = $request->getQuery('type');
        $status = $request->getQuery('status', 'pending');
        $priority = $request->getQuery('priority');
        $search = $request->getQuery('search');

        $filters = array_filter([
            'type' => $type,
            'status' => $status,
            'priority' => $priority,
            'search' => $search
        ]);

        try {
            // Use unified moderation service to get media queue
            $result = $this->moderationService->getModerationQueue($page, $perPage, 'media', $priority, $status);
            
            // Enhance with media-specific data
            foreach ($result['data'] as &$item) {
                if ($item['content_type'] === 'media') {
                    $media = $this->mediaModel->find($item['content_id']);
                    if ($media) {
                        $item['media_data'] = $media;
                        $item['file_type'] = $media['file_type'];
                        $item['file_size'] = $media['file_size'];
                        $item['mime_type'] = $media['mime_type'];
                        $item['is_flagged'] = $media['is_flagged'];
                        $item['flag_count'] = $media['flag_count'];
                        $item['download_count'] = $media['download_count'];
                        
                        // Add moderation status indicators
                        $item['moderation_status_label'] = $this->getModerationStatusLabel($media);
                        $item['moderation_badge_class'] = $this->getModerationBadgeClass($media);
                        $item['needs_urgent_attention'] = $this->needsUrgentAttention($media);
                        $item['days_pending'] = $this->getDaysPending($media);
                    }
                }
            }

            return $this->paginatedResponse($result['data'], $result['pagination'], [
                'filters' => $filters,
                'summary' => $this->getQueueSummary()
            ]);

        } catch (\Exception $e) {
            error_log("MediaModerationController::index error: " . $e->getMessage());
            return $this->errorResponse('Failed to load moderation queue: ' . $e->getMessage());
        }
    }

    /**
     * Show specific media for moderation
     */
    public function show(Request $request, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $mediaId = (int)$id;
        $media = $this->mediaModel->find($mediaId);
        
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        try {
            // Get uploader information
            $uploader = $this->userModel->find($media['uploader_id']);

            // Get usage information (where this media is used)
            $usage = $this->mediaModel->getMediaUsage($mediaId);

            // Get flagging history
            $flagHistory = $this->mediaModel->getMediaFlags($mediaId);
            
            // Get moderation history
            $moderationHistory = $this->mediaModel->getModerationHistory($mediaId);

            // Get moderation queue entry if exists
            $queueEntry = $this->db->fetch(
                "SELECT * FROM moderation_queue WHERE content_type = 'media' AND content_id = ? AND status IN ('pending', 'in_review')",
                [$mediaId]
            );

            // Get similar flagged media from same uploader
            $similarIssues = $this->getSimilarIssues($media['uploader_id'], $mediaId);

            $data = array_merge($media, [
                'uploader' => $uploader,
                'usage' => $usage,
                'flag_history' => $flagHistory,
                'moderation_history' => $moderationHistory,
                'queue_entry' => $queueEntry,
                'similar_issues' => $similarIssues,
                'moderation_recommendations' => $this->generateModerationRecommendations($media, $uploader, $flagHistory)
            ]);

            return $this->successResponse($data);

        } catch (\Exception $e) {
            error_log("MediaModerationController::show error: " . $e->getMessage());
            return $this->errorResponse('Failed to load media details: ' . $e->getMessage());
        }
    }

    /**
     * Moderate a specific media item - FIXED VERSION
     */
    public function moderate(Request $request, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $mediaId = (int)$id;
        $data = $request->getAllData();
        
        $media = $this->mediaModel->find($mediaId);
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        $action = $data['action'] ?? '';
        $reason = $data['reason'] ?? '';
        $moderatorId = $request->getUser()['id'];

        if (!in_array($action, ['approve', 'reject', 'flag', 'hide', 'restore', 'delete'])) {
            throw new ValidationException('Invalid moderation action');
        }

        try {
            // Don't start transaction here since the model methods handle their own transactions
            $success = $this->performModerationAction($mediaId, $action, $reason, $moderatorId);
            
            if (!$success) {
                return $this->errorResponse('Failed to perform moderation action');
            }

            // Update unified moderation queue
            $this->moderationService->updateModerationQueue('media', $mediaId, $action, $moderatorId);

            // Log the moderation action
            $this->mediaModel->logModerationAction($mediaId, $moderatorId, $action, $reason);

            // Send notification to uploader if appropriate
            if (in_array($action, ['approve', 'reject']) && $media['uploader_id']) {
                $this->sendModerationNotification($media, $action, $reason);
            }

            // Clear relevant caches
            $this->cache->deletePattern('media_*');
            $this->cache->deletePattern('moderation_*');

            return $this->successResponse([
                'action' => $action,
                'media_id' => $mediaId,
                'status' => $this->getNewStatus($action)
            ], 'Moderation action completed successfully');

        } catch (\Exception $e) {
            error_log("MediaModerationController::moderate error: " . $e->getMessage());
            return $this->errorResponse('Moderation failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk moderate multiple media items
     */
    public function bulkModerate(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getAllData();
        $mediaIds = $data['media_ids'] ?? [];
        $action = $data['action'] ?? '';
        $reason = $data['reason'] ?? '';
        $moderatorId = $request->getUser()['id'];

        if (empty($mediaIds) || !is_array($mediaIds)) {
            throw new ValidationException('Media IDs array is required');
        }

        if (!in_array($action, ['approve', 'reject', 'flag', 'hide', 'restore', 'delete'])) {
            throw new ValidationException('Invalid bulk moderation action');
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        try {
            $this->db->beginTransaction();

            foreach ($mediaIds as $mediaId) {
                try {
                    $mediaId = (int)$mediaId;
                    $media = $this->mediaModel->find($mediaId);
                    
                    if (!$media) {
                        $results['failed']++;
                        $results['errors'][] = "Media ID {$mediaId} not found";
                        continue;
                    }

                    $success = $this->performModerationAction($mediaId, $action, $reason, $moderatorId);
                    
                    if ($success) {
                        $results['success']++;
                        
                        // Update unified moderation queue
                        $this->moderationService->updateModerationQueue('media', $mediaId, $action, $moderatorId);
                        
                        // Log the action
                        $this->mediaModel->logModerationAction($mediaId, $moderatorId, $action, $reason);
                        
                        // Send notification if appropriate
                        if (in_array($action, ['approve', 'reject']) && $media['uploader_id']) {
                            $this->sendModerationNotification($media, $action, $reason);
                        }
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to {$action} media ID {$mediaId}";
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Error processing media ID {$mediaId}: " . $e->getMessage();
                }
            }

            // Clear relevant caches
            $this->cache->deletePattern('media_*');
            $this->cache->deletePattern('moderation_*');

            $this->db->commit();

            $message = "Bulk moderation completed: {$results['success']} successful, {$results['failed']} failed";

            return $this->successResponse($results, $message);

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("MediaModerationController::bulkModerate error: " . $e->getMessage());
            return $this->errorResponse('Bulk moderation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get moderation statistics
     */
    public function statistics(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $timeframe = $request->getQuery('timeframe', '7d');
        $cacheKey = $this->cache->generateKey('media_moderation_stats', $timeframe);
        
        $stats = $this->cache->remember($cacheKey, function() use ($timeframe) {
            return [
                'overview' => $this->getOverviewStatistics(),
                'timeline' => $this->getTimelineStatistics($timeframe),
                'flag_analysis' => $this->getFlagAnalysis(),
                'uploader_insights' => $this->getUploaderInsights(),
                'performance_metrics' => $this->getPerformanceMetrics($timeframe)
            ];
        }, 300); // Cache for 5 minutes

        return $this->successResponse($stats);
    }

    /**
     * Export moderation report
     */
    public function exportReport(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $format = $request->getQuery('format', 'csv');
        $dateFrom = $request->getQuery('date_from');
        $dateTo = $request->getQuery('date_to');
        $status = $request->getQuery('status');

        if (!in_array($format, ['csv', 'json'])) {
            throw new ValidationException('Invalid export format. Supported: csv, json');
        }

        try {
            $filename = $this->generateModerationReport($format, $dateFrom, $dateTo, $status);
            
            // Log activity
            $this->logActivity($request->getUser()['id'], 'media_report_exported', 'system', 0, [
                'format' => $format,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status
            ]);

            return $this->successResponse([
                'download_url' => '/api/admin/downloads/' . $filename,
                'filename' => $filename
            ], 'Report generated successfully');

        } catch (\Exception $e) {
            error_log("MediaModerationController::exportReport error: " . $e->getMessage());
            return $this->errorResponse('Report generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Flag media (admin version)
     */
    public function flagMedia(Request $request, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $mediaId = (int)$id;
        $data = $request->getAllData();
        
        $media = $this->mediaModel->find($mediaId);
        if (!$media) {
            throw new NotFoundException('Media not found');
        }

        $flagType = $data['flag_type'] ?? 'inappropriate';
        $reason = $data['reason'] ?? '';

        $validFlagTypes = ['inappropriate', 'copyright', 'spam', 'misleading', 'adult_content', 'violence', 'hate_speech', 'other'];
        
        if (!in_array($flagType, $validFlagTypes)) {
            throw new ValidationException('Invalid flag type');
        }

        if (empty($reason)) {
            throw new ValidationException('Flag reason is required');
        }

        try {
            // Use unified moderation service
            $success = $this->moderationService->flagContent(
                'media',
                $mediaId,
                $request->getUser()['id'],
                $flagType,
                $reason
            );

            if ($success) {
                // Log activity
                $this->logActivity($request->getUser()['id'], 'media_flagged', 'media', $mediaId, [
                    'flag_type' => $flagType,
                    'reason' => substr($reason, 0, 100)
                ]);

                return $this->successResponse([], 'Media flagged successfully');
            } else {
                return $this->errorResponse('Failed to flag media or already flagged');
            }

        } catch (\Exception $e) {
            error_log("MediaModerationController::flagMedia error: " . $e->getMessage());
            return $this->errorResponse('Failed to flag media: ' . $e->getMessage());
        }
    }

    // Private helper methods

    private function performModerationAction(int $mediaId, string $action, string $reason, int $moderatorId): bool
    {
        try {
            switch ($action) {
                case 'approve':
                    return $this->mediaModel->moderateApprove($mediaId, $moderatorId);
                    
                case 'reject':
                    return $this->mediaModel->moderateReject($mediaId, $moderatorId);
                    
                case 'flag':
                    return $this->mediaModel->moderateFlag($mediaId, $moderatorId);
                    
                case 'hide':
                    return $this->mediaModel->moderateHide($mediaId, $moderatorId);
                    
                case 'restore':
                    return $this->mediaModel->moderateRestore($mediaId, $moderatorId);
                    
                case 'delete':
                    return $this->fileUploadService->delete($mediaId, $moderatorId);
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log("Moderation action failed: " . $e->getMessage());
            return false;
        }
    }

    private function getNewStatus(string $action): string
    {
        return match($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'flag' => 'flagged',
            'hide' => 'hidden',
            'restore' => 'restored',
            'delete' => 'deleted',
            default => 'unknown'
        };
    }

    private function sendModerationNotification(array $media, string $action, string $reason): void
    {
        try {
            $uploader = $this->userModel->find($media['uploader_id']);
            if (!$uploader) return;

            $subject = 'Media ' . ucfirst($action) . 'd';
            $actionText = $action === 'approve' ? 'approved and is now available' : $action . 'd';
            
            $body = "
            <html>
            <body>
                <h2>Media Moderation Update</h2>
                <p>Hello {$uploader['first_name']},</p>
                <p>Your uploaded media file '{$media['original_filename']}' has been {$actionText}.</p>
                " . ($reason ? "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>" : "") . "
                <p><strong>File Details:</strong></p>
                <ul>
                    <li>File: {$media['original_filename']}</li>
                    <li>Type: {$media['file_type']}</li>
                    <li>Size: " . $this->formatFileSize($media['file_size']) . "</li>
                    <li>Uploaded: " . date('M j, Y', strtotime($media['created_at'])) . "</li>
                </ul>
                <p>Thank you for contributing to our platform.</p>
                <p>Best regards,<br>The Moderation Team</p>
            </body>
            </html>";

            $emailData = [
                'to_email' => $uploader['email'],
                'to_name' => $uploader['first_name'] . ' ' . $uploader['last_name'],
                'subject' => $subject,
                'body_html' => $body,
                'priority' => 'normal'
            ];

            $this->emailService->queue($emailData);

        } catch (\Exception $e) {
            error_log("Failed to send moderation notification: " . $e->getMessage());
        }
    }

    private function getSimilarIssues(int $uploaderId, int $excludeMediaId): array
    {
        $sql = "SELECT m.id, m.original_filename, m.file_type, m.flag_count, m.created_at
                FROM media m
                WHERE m.uploader_id = ? AND m.id != ? AND m.is_flagged = 1 AND m.deleted_at IS NULL
                ORDER BY m.flag_count DESC, m.created_at DESC
                LIMIT 5";
        
        return $this->db->fetchAll($sql, [$uploaderId, $excludeMediaId]);
    }

    private function generateModerationRecommendations(array $media, ?array $uploader, array $flagHistory): array
    {
        $recommendations = [];
        
        // Based on flag history
        if (!empty($flagHistory)) {
            $flagTypes = array_column($flagHistory, 'flag_type');
            $commonFlags = array_count_values($flagTypes);
            
            if (isset($commonFlags['copyright']) && $commonFlags['copyright'] > 1) {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => 'Multiple copyright flags detected. Verify ownership and licensing.',
                    'action' => 'verify_copyright'
                ];
            }
            
            if (isset($commonFlags['inappropriate']) && $commonFlags['inappropriate'] > 0) {
                $recommendations[] = [
                    'type' => 'caution',
                    'message' => 'Content flagged as inappropriate. Review against community guidelines.',
                    'action' => 'review_guidelines'
                ];
            }
        }
        
        // Based on uploader history
        if ($uploader) {
            $uploaderStats = $this->db->fetch(
                "SELECT COUNT(*) as total, COUNT(CASE WHEN is_rejected = 1 THEN 1 END) as rejected 
                 FROM media WHERE uploader_id = ? AND deleted_at IS NULL",
                [$uploader['id']]
            );
            
            if ($uploaderStats && $uploaderStats['total'] > 0) {
                $rejectionRate = ($uploaderStats['rejected'] / $uploaderStats['total']) * 100;
                
                if ($rejectionRate > 50) {
                    $recommendations[] = [
                        'type' => 'alert',
                        'message' => 'High rejection rate (' . round($rejectionRate, 1) . '%) for this uploader.',
                        'action' => 'review_uploader_pattern'
                    ];
                }
            }
        }
        
        // Based on file characteristics
        if ($media['file_size'] > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Large file size. Consider storage impact and loading performance.',
                'action' => 'check_optimization'
            ];
        }
        
        return $recommendations;
    }

    private function getQueueSummary(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_pending,
                    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_count,
                    COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_count,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_age_hours
                FROM moderation_queue 
                WHERE content_type = 'media' AND status = 'pending'";
        
        return $this->db->fetch($sql) ?: [];
    }

    private function getOverviewStatistics(): array
    {
        return $this->mediaModel->getMediaStatistics();
    }

    private function getTimelineStatistics(string $timeframe): array
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
                    COUNT(*) as total_uploaded,
                    COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved,
                    COUNT(CASE WHEN is_rejected = 1 THEN 1 END) as rejected,
                    COUNT(CASE WHEN is_flagged = 1 THEN 1 END) as flagged
                FROM media 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND deleted_at IS NULL
                GROUP BY DATE(created_at)
                ORDER BY date DESC";

        return $this->db->fetchAll($sql, [$days]);
    }

    private function getFlagAnalysis(): array
    {
        $sql = "SELECT 
                    flag_type,
                    COUNT(*) as count,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
                FROM media_flags 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY flag_type
                ORDER BY count DESC";

        return $this->db->fetchAll($sql);
    }

    private function getUploaderInsights(): array
    {
        $sql = "SELECT * FROM (
                    SELECT 
                        u.username,
                        u.first_name,
                        u.last_name,
                        COUNT(m.id) as total_uploads,
                        SUM(m.is_flagged = 1) as flagged_uploads,
                        SUM(m.is_rejected = 1) as rejected_uploads
                    FROM users u
                    INNER JOIN media m ON u.id = m.uploader_id
                    WHERE m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                      AND m.deleted_at IS NULL
                    GROUP BY u.id, u.username, u.first_name, u.last_name
                ) AS stats
                WHERE flagged_uploads > 0 OR rejected_uploads > 0
                ORDER BY (flagged_uploads + rejected_uploads) DESC
                LIMIT 10";

        return $this->db->fetchAll($sql);
    }

   private function getPerformanceMetrics(string $timeframe): array
   {
       $days = match($timeframe) {
           '24h' => 1,
           '7d' => 7,
           '30d' => 30,
           '90d' => 90,
           default => 7
       };

       $sql = "SELECT 
                   COUNT(*) as total_moderated,
                   CAST(AVG(TIMESTAMPDIFF(HOUR, mq.created_at, mq.reviewed_at)) AS DECIMAL(10,2)) as avg_response_hours,
                   SUM(mq.status = 'approved') as approved_count,
                   SUM(mq.status = 'rejected') as rejected_count,
                   SUM(mq.status NOT IN ('approved', 'rejected')) as other_count
               FROM moderation_queue mq
               WHERE mq.content_type = 'media' 
               AND mq.reviewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND mq.reviewed_at IS NOT NULL";

       return $this->db->fetch($sql, [$days]) ?: [];
   }

    private function generateModerationReport(string $format, ?string $dateFrom, ?string $dateTo, ?string $status): string
    {
        $whereConditions = ['m.deleted_at IS NULL'];
        $params = [];
        
        if ($dateFrom) {
            $whereConditions[] = 'm.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $whereConditions[] = 'm.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        if ($status) {
            switch ($status) {
                case 'pending':
                    $whereConditions[] = 'm.is_approved = 0 AND m.is_rejected = 0';
                    break;
                case 'approved':
                    $whereConditions[] = 'm.is_approved = 1';
                    break;
                case 'rejected':
                    $whereConditions[] = 'm.is_rejected = 1';
                    break;
                case 'flagged':
                    $whereConditions[] = 'm.is_flagged = 1';
                    break;
            }
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    m.id, m.original_filename, m.file_type, m.file_size, 
                    m.is_approved, m.is_rejected, m.is_flagged, m.flag_count, 
                    m.created_at, m.moderated_at,
                    u.username, u.email, u.first_name, u.last_name,
                    mod.username as moderated_by_username,
                    mq.priority, mq.status as queue_status
                FROM media m
                LEFT JOIN users u ON m.uploader_id = u.id
                LEFT JOIN users mod ON m.moderated_by = mod.id
                LEFT JOIN moderation_queue mq ON mq.content_type = 'media' AND mq.content_id = m.id
                {$whereClause}
                ORDER BY m.created_at DESC";
        
        $data = $this->db->fetchAll($sql, $params);
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "media_moderation_report_{$timestamp}.{$format}";
        $filepath = dirname(__DIR__, 4) . '/storage/exports/' . $filename;
        
        // Create exports directory if it doesn't exist
        $exportDir = dirname($filepath);
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        if ($format === 'csv') {
            $this->exportToCsv($data, $filepath);
        } elseif ($format === 'json') {
            $this->exportToJson($data, $filepath);
        } else {
            throw new \InvalidArgumentException('Unsupported export format');
        }
        
        return $filename;
    }

    private function exportToCsv(array $data, string $filepath): void
    {
        $file = fopen($filepath, 'w');
        
        // Write header
        $headers = [
            'ID', 'Filename', 'File Type', 'File Size', 'Uploader', 'Uploader Email',
            'Status', 'Flags', 'Priority', 'Upload Date', 'Moderated By', 'Moderated Date'
        ];
        fputcsv($file, $headers);
        
        // Write data
        foreach ($data as $row) {
            $status = 'Pending';
            if ($row['is_approved']) $status = 'Approved';
            elseif ($row['is_rejected']) $status = 'Rejected';
            elseif ($row['is_flagged']) $status = 'Flagged';
            
            fputcsv($file, [
                $row['id'],
                $row['original_filename'],
                $row['file_type'],
                $this->formatFileSize($row['file_size']),
                $row['username'] ?? 'Unknown',
                $row['email'] ?? '',
                $status,
                $row['flag_count'],
                $row['priority'] ?? 'N/A',
                $row['created_at'],
                $row['moderated_by_username'] ?? '',
                $row['moderated_at'] ?? ''
            ]);
        }
        
        fclose($file);
    }

    private function exportToJson(array $data, string $filepath): void
    {
        $processedData = array_map(function($row) {
            $row['file_size_formatted'] = $this->formatFileSize($row['file_size']);
            $row['status'] = 'Pending';
            if ($row['is_approved']) $row['status'] = 'Approved';
            elseif ($row['is_rejected']) $row['status'] = 'Rejected';
            elseif ($row['is_flagged']) $row['status'] = 'Flagged';
            
            return $row;
        }, $data);
        
        file_put_contents($filepath, json_encode($processedData, JSON_PRETTY_PRINT));
    }

    private function getModerationStatusLabel(array $media): string
    {
        if ($media['is_flagged']) {
            return 'Flagged (' . ($media['flag_count'] ?? 0) . ')';
        } elseif ($media['is_rejected']) {
            return 'Rejected';
        } elseif ($media['is_approved']) {
            return 'Approved';
        } else {
            return 'Pending Review';
        }
    }

    private function getModerationBadgeClass(array $media): string
    {
        if ($media['is_flagged']) {
            return 'badge-danger';
        } elseif ($media['is_rejected']) {
            return 'badge-warning';
        } elseif ($media['is_approved']) {
            return 'badge-success';
        } else {
            return 'badge-info';
        }
    }

    private function needsUrgentAttention(array $media): bool
    {
        return $media['is_flagged'] && $media['flag_count'] >= 3;
    }

    private function getDaysPending(array $media): int
    {
        if ($media['is_approved'] || $media['is_rejected']) {
            return 0;
        }
        
        $createdAt = new \DateTime($media['created_at']);
        $now = new \DateTime();
        return $now->diff($createdAt)->days;
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
    {
        try {
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
        } catch (\Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}