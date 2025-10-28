<?php

// api/src/Controllers/Admin/CommentModerationController.php - Unified Admin Comment Moderation

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Services\CommentModerationService;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

class CommentModerationController extends BaseController
{
    private CommentModerationService $moderationService;

    public function __construct(
        Database $database,
        CommentModerationService $moderationService
    ) {
        parent::__construct();
        $this->db = $database;
        $this->moderationService = $moderationService;
    }

    /**
     * Get moderation queue
     */
    public function queue(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }
        
        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 20), 100);
        
        $filters = [
            'priority' => $request->getQuery('priority'),
            'flagged_only' => $request->getQueryBool('flagged_only', false)
        ];

        try {
            $result = $this->moderationService->getModerationQueue($page, $perPage, $filters);
            return $this->paginatedResponse($result['data'], $result['pagination']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch moderation queue: ' . $e->getMessage());
        }
    }

    /**
     * Show specific comment for moderation
     */
    public function show(Request $request, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $commentId = (int)$id;
        
        try {
            $sql = "SELECT 
                        c.*,
                        u.username,
                        u.first_name,
                        u.last_name,
                        u.email,
                        u.role,
                        u.profile_image,
                        mq.priority,
                        mq.flags,
                        mq.reasons,
                        mq.moderation_score as queue_score,
                        CASE 
                            WHEN c.content_type = 'news' THEN n.title
                            WHEN c.content_type = 'event' THEN e.title
                        END as content_title
                    FROM comments c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN moderation_queue mq ON mq.content_type = 'comment' AND mq.content_id = c.id
                    LEFT JOIN news n ON c.content_type = 'news' AND c.content_id = n.id
                    LEFT JOIN events e ON c.content_type = 'event' AND c.content_id = e.id
                    WHERE c.id = ? AND c.deleted_at IS NULL";
            
            $comment = $this->db->fetch($sql, [$commentId]);
            
            if (!$comment) {
                throw new NotFoundException('Comment not found');
            }

            // Parse JSON fields
            if ($comment['flags']) {
                $comment['flags'] = json_decode($comment['flags'], true);
            }
            if ($comment['reasons']) {
                $comment['reasons'] = json_decode($comment['reasons'], true);
            }

            return $this->successResponse($comment);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch comment: ' . $e->getMessage());
        }
    }

    /**
     * Moderate a comment
     */
    public function moderate(Request $request, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $commentId = (int)$id;
        $data = $request->getAllData();
        
        if (empty($data['action'])) {
            throw new ValidationException('Action is required');
        }

        $action = $data['action'];
        $reason = $data['reason'] ?? '';

        if (!in_array($action, ['approve', 'reject', 'delete', 'spam'])) {
            throw new ValidationException('Invalid action');
        }

        try {
            $success = $this->moderationService->moderateComment(
                $commentId, 
                $action, 
                $request->getUser()['id'], 
                $reason
            );

            if ($success) {
                return $this->successResponse([], "Comment {$action}d successfully");
            } else {
                return $this->errorResponse("Failed to {$action} comment");
            }
        } catch (\Exception $e) {
            return $this->errorResponse("Failed to {$action} comment: " . $e->getMessage());
        }
    }

    /**
     * Bulk moderate comments
     */
    public function bulkModerate(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getAllData();
        
        if (empty($data['comment_ids']) || !is_array($data['comment_ids'])) {
            throw new ValidationException('Comment IDs array is required');
        }

        if (empty($data['action'])) {
            throw new ValidationException('Action is required');
        }

        $commentIds = array_map('intval', $data['comment_ids']);
        $action = $data['action'];
        $reason = $data['reason'] ?? '';

        if (!in_array($action, ['approve', 'reject', 'delete', 'spam'])) {
            throw new ValidationException('Invalid action');
        }

        try {
            $results = $this->moderationService->bulkModerateComments(
                $commentIds, 
                $action, 
                $request->getUser()['id'], 
                $reason
            );

            return $this->successResponse($results, "Bulk moderation completed: {$results['success']} successful, {$results['failed']} failed");
        } catch (\Exception $e) {
            return $this->errorResponse('Bulk moderation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get flagged comments
     */
    public function flaggedComments(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 20), 100);

        try {
            $result = $this->moderationService->getFlaggedComments($page, $perPage);
            return $this->paginatedResponse($result['data'], $result['pagination']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch flagged comments: ' . $e->getMessage());
        }
    }

    /**
     * Auto-moderate comments
     */
    public function autoModerate(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $limit = min($request->getQueryInt('limit', 50), 100);

        try {
            $results = $this->moderationService->autoModeratePendingComments($limit);
            return $this->successResponse($results, 'Auto-moderation completed');
        } catch (\Exception $e) {
            return $this->errorResponse('Auto-moderation failed: ' . $e->getMessage());
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

        try {
            $stats = $this->moderationService->getModerationStatistics();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch statistics: ' . $e->getMessage());
        }
    }

    /**
     * Handle comment flags
     */
    public function getFlags(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 20), 100);
        $status = $request->getQuery('status', 'pending');

        try {
            $sql = "SELECT 
                        cf.*,
                        c.content,
                        c.author_name,
                        u1.username as reporter_username,
                        u2.username as reviewer_username
                    FROM comment_flags cf
                    INNER JOIN comments c ON cf.comment_id = c.id
                    LEFT JOIN users u1 ON cf.user_id = u1.id
                    LEFT JOIN users u2 ON cf.reviewed_by = u2.id
                    WHERE cf.status = ?
                    ORDER BY cf.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $offset = ($page - 1) * $perPage;
            $flags = $this->db->fetchAll($sql, [$status, $perPage, $offset]);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM comment_flags WHERE status = ?";
            $total = $this->db->fetch($countSql, [$status])['total'];

            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_previous' => $page > 1
            ];

            return $this->paginatedResponse($flags, $pagination);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch flags: ' . $e->getMessage());
        }
    }

    /**
     * Handle a specific flag
     */
    public function handleFlag(Request $request, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $flagId = (int)$id;
        $data = $request->getAllData();

        if (empty($data['action']) || !in_array($data['action'], ['resolve', 'dismiss'])) {
            throw new ValidationException('Valid action (resolve, dismiss) is required');
        }

        $action = $data['action'];
        $adminNotes = $data['admin_notes'] ?? '';

        try {
            $sql = "UPDATE comment_flags 
                    SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_notes = ?
                    WHERE id = ?";
            
            $newStatus = $action === 'resolve' ? 'resolved' : 'dismissed';
            $updated = $this->db->execute($sql, [$newStatus, $request->getUser()['id'], $adminNotes, $flagId]);

            if ($updated) {
                return $this->successResponse([], 'Flag handled successfully');
            } else {
                return $this->errorResponse('Flag not found or already processed');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to handle flag: ' . $e->getMessage());
        }
    }
}