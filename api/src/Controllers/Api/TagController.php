<?php

// api/src/Controllers/Api/TagController.php - Tag API Controller

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Tag;
use App\Services\CacheService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;

class TagController extends BaseController
{
    private Tag $tagModel;
    private CacheService $cache;

    public function __construct(Tag $tagModel, CacheService $cache)
    {
        $this->tagModel = $tagModel;
        $this->cache = $cache;
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
        $search = $request->getQuery('search');
        
        if ($search) {
            $tags = $this->tagModel->searchTags($search, $perPage * $page);
            
            // Simple pagination for search results
            $offset = ($page - 1) * $perPage;
            $pagedTags = array_slice($tags, $offset, $perPage);
            
            return $this->response->json([
                'success' => true,
                'data' => $pagedTags,
                'meta' => [
                    'search' => $search,
                    'total_found' => count($tags)
                ]
            ]);
        }
        
        $result = $this->tagModel->paginate($page, $perPage);

        return $this->paginatedResponse($result['data'], $result['pagination']);
    }

    public function show(Request $request, string $identifier): Response
    {
        // Try to find by ID first, then by slug
        if (is_numeric($identifier)) {
            $tag = $this->tagModel->find((int)$identifier);
        } else {
            $tag = $this->tagModel->findBySlug($identifier);
        }
        
        if (!$tag) {
            throw new NotFoundException('Tag not found');
        }

        // Get tag usage statistics
        $usage = $this->tagModel->getTagUsage($tag['id']);
        
        // Get recent content for this tag
        $recentContent = $this->tagModel->getContentByTag($tag['id'], null, 1, 5);

        $data = array_merge($tag, [
            'usage' => $usage,
            'recent_content' => $recentContent['data']
        ]);

        return $this->response->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function create(Request $request): Response
    {
        // Only contributors and admins can create tags
        if (!in_array($request->user['role'], ['contributor', 'super_admin'])) {
            throw new AuthorizationException('Contributor access required');
        }

        $data = $request->getAllData();
        
        $validation = $this->validateTag($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Check if tag already exists
        if ($this->tagModel->findByName($data['name'])) {
            return $this->response->json([
                'success' => false,
                'message' => 'Tag already exists'
            ], 400);
        }

        $tag = $this->tagModel->createTag($data);
        
        if (!$tag) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create tag'
            ], 400);
        }

        // Clear tag cache
        $this->cache->deletePattern('tags_%');

        // Log activity
        $this->logActivity($request->user['id'], 'tag_created', 'tag', $tag['id']);

        return $this->response->json([
            'success' => true,
            'message' => 'Tag created successfully',
            'data' => $tag
        ], 201);
    }

    public function update(Request $request, string $id): Response
    {
        // Only admin can update tags
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $tagId = (int)$id;
        $data = $request->getAllData();
        
        $existingTag = $this->tagModel->find($tagId);
        if (!$existingTag) {
            throw new NotFoundException('Tag not found');
        }

        $validation = $this->validateTag($data, $tagId);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $updatedTag = $this->tagModel->updateTag($tagId, $data);
        
        if (!$updatedTag) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update tag'
            ], 400);
        }

        // Clear tag cache
        $this->cache->deletePattern('tags_%');

        // Log activity
        $this->logActivity($request->user['id'], 'tag_updated', 'tag', $tagId);

        return $this->response->json([
            'success' => true,
            'message' => 'Tag updated successfully',
            'data' => $updatedTag
        ]);
    }

    public function delete(Request $request, string $id): Response
    {
        // Only admin can delete tags
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $tagId = (int)$id;
        
        $tag = $this->tagModel->find($tagId);
        if (!$tag) {
            throw new NotFoundException('Tag not found');
        }

        // Check if tag can be deleted (no content associated)
        if (!$this->tagModel->canDelete($tagId)) {
            return $this->response->json([
                'success' => false,
                'message' => 'Cannot delete tag with associated content',
                'data' => $this->tagModel->getTagUsage($tagId)
            ], 400);
        }

        $deleted = $this->tagModel->delete($tagId);
        
        if (!$deleted) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete tag'
            ], 400);
        }

        // Clear tag cache
        $this->cache->deletePattern('tags_%');

        // Log activity
        $this->logActivity($request->user['id'], 'tag_deleted', 'tag', $tagId);

        return $this->response->json([
            'success' => true,
            'message' => 'Tag deleted successfully'
        ]);
    }

    public function popular(Request $request): Response
    {
        $limit = min((int)($request->getQuery('limit') ?? 20), 100);
        
        $cacheKey = $this->cache->generateKey('tags_popular', $limit);
        
        $tags = $this->cache->remember($cacheKey, function() use ($limit) {
            return $this->tagModel->getPopularTags($limit);
        }, 1800); // Cache for 30 minutes

        return $this->response->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    public function trending(Request $request): Response
    {
        $days = min((int)($request->getQuery('days') ?? 30), 365);
        $limit = min((int)($request->getQuery('limit') ?? 10), 50);
        
        $cacheKey = $this->cache->generateKey('tags_trending', $days, $limit);
        
        $tags = $this->cache->remember($cacheKey, function() use ($days, $limit) {
            return $this->tagModel->getTrendingTags($days, $limit);
        }, 900); // Cache for 15 minutes

        return $this->response->json([
            'success' => true,
            'data' => $tags,
            'meta' => [
                'days' => $days,
                'limit' => $limit
            ]
        ]);
    }

    public function search(Request $request): Response
    {
        $query = $request->getQuery('q');
        $limit = min((int)($request->getQuery('limit') ?? 20), 100);
        
        if (empty($query)) {
            return $this->response->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $tags = $this->tagModel->searchTags($query, $limit);

        return $this->response->json([
            'success' => true,
            'data' => $tags,
            'meta' => [
                'query' => $query,
                'total_found' => count($tags)
            ]
        ]);
    }

    public function content(Request $request, string $id): Response
    {
        $tagId = (int)$id;
        $contentType = $request->getQuery('type'); // 'news' or 'event'
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        
        $tag = $this->tagModel->find($tagId);
        if (!$tag) {
            throw new NotFoundException('Tag not found');
        }

        $result = $this->tagModel->getContentByTag($tagId, $contentType, $page, $perPage);

        return $this->response->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => [
                'tag' => $tag,
                'content_type' => $contentType,
                'pagination' => $result['pagination']
            ]
        ]);
    }

    public function statistics(Request $request): Response
    {
        // Only admin can view tag statistics
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $cacheKey = $this->cache->generateKey('tag_statistics');
        
        $stats = $this->cache->remember($cacheKey, function() {
            return $this->tagModel->getTagStatistics();
        }, 300); // Cache for 5 minutes

        return $this->response->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function cleanup(Request $request): Response
    {
        // Only admin can cleanup tags
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $olderThanDays = (int)($request->getAllData('older_than_days') ?? 30);
        $dryRun = $request->getAllData('dry_run') === true;
        
        if ($dryRun) {
            // Just return what would be deleted
            $unusedTags = $this->tagModel->getUnusedTags();
            $eligibleForDeletion = array_filter($unusedTags, function($tag) use ($olderThanDays) {
                $createdAt = new \DateTime($tag['created_at']);
                $cutoffDate = new \DateTime("-{$olderThanDays} days");
                return $createdAt < $cutoffDate;
            });
            
            return $this->response->json([
                'success' => true,
                'message' => 'Dry run completed',
                'data' => [
                    'tags_to_delete' => count($eligibleForDeletion),
                    'tags' => $eligibleForDeletion
                ]
            ]);
        }
        
        $deletedCount = $this->tagModel->cleanupUnusedTags($olderThanDays);
        
        // Clear tag cache
        $this->cache->deletePattern('tags_%');

        // Log activity
        $this->logActivity($request->user['id'], 'tags_cleanup', 'system', 0, [
            'deleted_count' => $deletedCount,
            'older_than_days' => $olderThanDays
        ]);

        return $this->response->json([
            'success' => true,
            'message' => "Cleaned up {$deletedCount} unused tags",
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ]);
    }

    public function recalculateUsage(Request $request): Response
    {
        // Only admin can recalculate usage
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $updatedCount = $this->tagModel->recalculateUsageCounts();
        
        // Clear tag cache
        $this->cache->deletePattern('tags_%');

        // Log activity
        $this->logActivity($request->user['id'], 'tag_usage_recalculated', 'system', 0, [
            'updated_count' => $updatedCount
        ]);

        return $this->response->json([
            'success' => true,
            'message' => "Recalculated usage counts for {$updatedCount} tags",
            'data' => [
                'updated_count' => $updatedCount
            ]
        ]);
    }

    public function merge(Request $request): Response
    {
        // Only admin can merge tags
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getAllData();
        
        if (empty($data['source_tag_id']) || empty($data['target_tag_id'])) {
            return $this->response->json([
                'success' => false,
                'message' => 'Source and target tag IDs are required'
            ], 400);
        }

        $sourceTagId = (int)$data['source_tag_id'];
        $targetTagId = (int)$data['target_tag_id'];
        
        if ($sourceTagId === $targetTagId) {
            return $this->response->json([
                'success' => false,
                'message' => 'Source and target tags cannot be the same'
            ], 400);
        }

        $sourceTag = $this->tagModel->find($sourceTagId);
        $targetTag = $this->tagModel->find($targetTagId);
        
        if (!$sourceTag || !$targetTag) {
            throw new NotFoundException('One or both tags not found');
        }

        $success = $this->tagModel->mergeTags($sourceTagId, $targetTagId);
        
        if (!$success) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to merge tags'
            ], 400);
        }

        // Clear tag cache
        $this->cache->deletePattern('tags_%');

        // Log activity
        $this->logActivity($request->user['id'], 'tags_merged', 'tag', $targetTagId, [
            'source_tag' => $sourceTag['name'],
            'target_tag' => $targetTag['name']
        ]);

        return $this->response->json([
            'success' => true,
            'message' => 'Tags merged successfully'
        ]);
    }

    private function validateTag(array $data, int $excludeId = null): array
    {
        $errors = [];

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Tag name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Tag name must be at least 2 characters';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Tag name must not exceed 100 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-_#]+$/', $data['name'])) {
            $errors['name'] = 'Tag name contains invalid characters';
        }

        // Check for duplicate name (excluding current tag if updating)
        if (!empty($data['name'])) {
            $existing = $this->tagModel->findByName($data['name']);
            if ($existing && (!$excludeId || $existing['id'] !== $excludeId)) {
                $errors['name'] = 'Tag name already exists';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
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
}