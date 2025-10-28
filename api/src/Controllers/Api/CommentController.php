<?php

// api/src/Controllers/Api/CommentController.php - Fixed Comment API Controller

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\Comment;
use App\Models\User;
use App\Models\News;
use App\Services\CommentModerationService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;

class CommentController extends BaseController
{
    private Comment $commentModel;
    private User $userModel;
    private News $newsModel;
    private CommentModerationService $moderationService;

    public function __construct(
        Database $database,
        Comment $commentModel,
        User $userModel,
        News $newsModel,
        CommentModerationService $moderationService
    ) {
        parent::__construct();
        $this->db = $database;
        $this->commentModel = $commentModel;
        $this->userModel = $userModel;
        $this->newsModel = $newsModel;
        $this->moderationService = $moderationService;
    }

    /**
     * Get comments for content (news/event)
     */
    public function getByContent(Request $request): Response
    {
        $path = $request->getPath();
        
        // Extract content type and ID from path
        if (preg_match('#/(news|events)/(\d+)/comments#', $path, $matches)) {
            $contentType = $matches[1] === 'news' ? 'news' : 'event';
            $contentId = (int)$matches[2];
        } else {
            return $this->errorResponse('Invalid URL format');
        }

        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 20), 50);
        $sortBy = $request->getQuery('sort', 'newest');

        try {
            $result = $this->commentModel->getByContent($contentType, $contentId, $page, $perPage, $sortBy);
            return $this->paginatedResponse($result['data'], $result['pagination']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch comments: ' . $e->getMessage());
        }
    }

    /**
     * Create a new comment
     */
    public function create(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthorizationException('Authentication required');
        }

        $path = $request->getPath();
        
        // Extract content type and ID from path
        $contentId = 0;
        if (preg_match('#/(news|events)/(\d+)/comments#', $path, $matches)) {
            $contentType = $matches[1] === 'news' ? 'news' : 'event';
            $contentId = (int)$matches[2];
        } else {
            return $this->errorResponse('Invalid URL format');
        }

        $data = $request->getAllData();

        $contentId = $data['content_id'];

        // Validate required fields
        if (empty($data['content'])) {
            throw new ValidationException('Comment content is required');
        }

        if (strlen($data['content']) < 3) {
            throw new ValidationException('Comment must be at least 3 characters long');
        }

        if (strlen($data['content']) > 2000) {
            throw new ValidationException('Comment must not exceed 2000 characters');
        }

        $user_info = $this->userModel->findById($request->getUser()['id']);

        // Prepare comment data
        $commentData = [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'user_id' => $user['id'],
            'author_name' => $user_info['first_name'] . ' ' . $user_info['last_name'],
            'author_email' => $user['email'],
            'content' => trim($data['content']),
            'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'ip_address' => $request->getIp(),
            'user_agent' => $request->getUserAgent(),
            'status' => 'pending' // Will be updated by moderation service
        ];

        try {
            // Use the moderation service to create and moderate the comment
            $comment = $this->moderationService->createComment($commentData);
            
            return $this->successResponse($comment, 'Comment created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create comment: ' . $e->getMessage());
        }
    }

    /**
     * Update a comment
     */
    public function update(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthorizationException('Authentication required');
        }

        $commentId = (int)$id;
        $comment = $this->commentModel->find($commentId);
        
        if (!$comment) {
            throw new NotFoundException('Comment not found');
        }

        // Check if user can edit this comment
        if ($comment['user_id'] !== $user['id'] && $user['role'] !== 'super_admin') {
            throw new AuthorizationException('You can only edit your own comments');
        }

        $data = $request->getAllData();
        
        if (empty($data['content'])) {
            throw new ValidationException('Comment content is required');
        }

        if (strlen($data['content']) < 3 || strlen($data['content']) > 2000) {
            throw new ValidationException('Comment must be between 3 and 2000 characters');
        }

        try {
            $updated = $this->commentModel->update($commentId, [
                'content' => trim($data['content']),
                'edit_count' => $comment['edit_count'] + 1,
                'last_edited_at' => date('Y-m-d H:i:s'),
                'status' => 'pending' // Re-moderate after edit
            ]);

            if ($updated) {
                return $this->successResponse($this->commentModel->find($commentId), 'Comment updated successfully');
            } else {
                return $this->errorResponse('Failed to update comment');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update comment: ' . $e->getMessage());
        }
    }

    /**
     * Delete a comment
     */
    public function delete(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthorizationException('Authentication required');
        }

        $commentId = (int)$id;
        $comment = $this->commentModel->find($commentId);
        
        if (!$comment) {
            throw new NotFoundException('Comment not found');
        }

        // Check permissions
        if ($comment['user_id'] !== $user['id'] && !in_array($user['role'], ['super_admin', 'contributor'])) {
            throw new AuthorizationException('You can only delete your own comments');
        }

        try {
            $this->db->beginTransaction();
            
            $deleted = $this->commentModel->delete($commentId);
            
            if ($deleted) {
                if ($comment['content_type'] === 'news') {
                    $this->newsModel->decrementCommentCount($comment['content_id']);
                } else {
                    $this->eventModel->decrementCommentCount($comment['content_id']);
                }
                $this->db->commit();
                return $this->successResponse([], 'Comment deleted successfully');
            }
            
            $this->db->rollBack();
            return $this->errorResponse('Failed to delete comment');
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->errorResponse('Failed to delete comment: ' . $e->getMessage());
        }
    }

    /**
     * Vote on a comment
     */
    public function vote(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthorizationException('Authentication required');
        }

        $commentId = (int)$id;
        $data = $request->getAllData();
        $voteType = $data['vote_type'] ?? '';

        if (!in_array($voteType, ['up', 'down'])) {
            throw new ValidationException('Invalid vote type. Must be "up" or "down"');
        }

        try {
            $result = $this->commentModel->voteComment($commentId, $user['id'], $voteType);
            return $this->successResponse($result, 'Vote recorded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to vote: ' . $e->getMessage());
        }
    }

    /**
     * Flag a comment
     */
    public function flag(Request $request, string $id): Response
    {
        $user = $request->getUser();
        $commentId = (int)$id;
        $data = $request->getAllData();
        
        $flagType = $data['flag_type'] ?? 'inappropriate';
        $reason = $data['reason'] ?? '';

        if (!in_array($flagType, ['spam', 'inappropriate', 'harassment', 'hate_speech', 'off_topic', 'other'])) {
            throw new ValidationException('Invalid flag type');
        }

        try {
            $flagged = $this->commentModel->flagComment(
                $commentId, 
                $user['id'] ?? null, 
                $flagType,
                $reason
            );
            
            if ($flagged) {
                $this->newsModel->decrementCommentCount($commentId);
                return $this->successResponse([], 'Comment flagged successfully');
            } else {
                return $this->errorResponse('You have already flagged this comment');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to flag comment: ' . $e->getMessage());
        }
    }

    /**
     * Get user's comments
     */
    public function getUserComments(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthorizationException('Authentication required');
        }

        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 20), 50);
        $status = $request->getQuery('status', 'all');

        try {
            $result = $this->commentModel->getUserComments($user['id'], $page, $perPage, $status);
            return $this->paginatedResponse($result['data'], $result['pagination']);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch comments: ' . $e->getMessage());
        }
    }

    /**
     * Get replies to a comment
     */
    public function getReplies(Request $request, string $id): Response
    {
        $parentId = (int)$id;
        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 10), 20);

        try {
            $replies = $this->commentModel->getReplies($parentId, $page, $perPage);
            
            if (is_array($replies) && isset($replies['data'])) {
                return $this->paginatedResponse($replies['data'], $replies['pagination']);
            } else {
                // Legacy format - simple array
                return $this->successResponse($replies);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch replies: ' . $e->getMessage());
        }
    }

    /**
     * Get trending comments
     */
    public function getTrending(Request $request): Response
    {
        $days = $request->getQueryInt('days', 7);
        $limit = min($request->getQueryInt('limit', 10), 50);

        try {
            $trending = $this->commentModel->getTrendingComments($days, $limit);
            return $this->successResponse($trending);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch trending comments: ' . $e->getMessage());
        }
    }

    /**
     * Get comment statistics for content
     */
    public function getCommentStats(Request $request): Response
    {
        $path = $request->getPath();
        
        // Extract content type and ID from path
        if (preg_match('#/(news|events)/(\d+)/comments/stats#', $path, $matches)) {
            $contentType = $matches[1] === 'news' ? 'news' : 'event';
            $contentId = (int)$matches[2];
        } else {
            return $this->errorResponse('Invalid URL format');
        }

        try {
            $stats = $this->commentModel->getContentCommentStats($contentType, $contentId);
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch comment statistics: ' . $e->getMessage());
        }
    }
}