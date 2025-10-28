<?php

// api/src/Controllers/Admin/NewsManagementController.php - API Controller for Admin News Operations

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\News;
use App\Models\Category;
use App\Models\User;
use App\Services\CacheService;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;

class NewsManagementController extends BaseController
{
    private News $newsModel;
    private Category $categoryModel;
    private User $userModel;
    private CacheService $cache;

    public function __construct(
        News $newsModel,
        Category $categoryModel,
        User $userModel,
        CacheService $cache
    ) {
        $this->newsModel = $newsModel;
        $this->categoryModel = $categoryModel;
        $this->userModel = $userModel;
        $this->cache = $cache;
        parent::__construct();
    }

    public function index(Request $request): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 20), 100);
        $status = $request->getQuery('status');
        $search = $request->getQuery('search');
        $categoryId = $request->getQuery('category_id');
        $authorId = $request->getQuery('author_id');

        if ($search) {
            $filters = array_filter([
                'status' => $status,
                'category_id' => $categoryId,
                'author_id' => $authorId
            ]);
            $result = $this->newsModel->searchNews($search, $page, $perPage, $filters);
        } elseif ($status) {
            $result = $this->newsModel->getByStatus($status, $page, $perPage);
        } else {
            $conditions = array_filter([
                'category_id' => $categoryId,
                'author_id' => $authorId
            ]);
            $result = $this->newsModel->paginate($page, $perPage, $conditions);
        }

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => [
                'status' => $status,
                'search' => $search,
                'category_id' => $categoryId,
                'author_id' => $authorId
            ]
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $newsId = (int)$id;
        $news = $this->newsModel->getWithDetails($newsId);
        
        if (!$news) {
            throw new NotFoundException('News article not found');
        }

        return $this->successResponse($news);
    }

    public function updateStatus(Request $request, string $id): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $newsId = (int)$id;
        $data = $request->getData();
        
        if (empty($data['status'])) {
            return $this->errorResponse('Status is required');
        }

        $news = $this->newsModel->find($newsId);
        if (!$news) {
            throw new NotFoundException('News article not found');
        }

        $oldStatus = $news['status'];
        $newStatus = $data['status'];

        $success = match($newStatus) {
            'published' => $this->newsModel->approveNews($newsId, $request->user['id']),
            'rejected' => $this->newsModel->rejectNews($newsId, $request->user['id']),
            default => $this->newsModel->update($newsId, ['status' => $newStatus]) !== null
        };

        if ($success) {
            // Clear cache
            $this->cache->deletePattern('news_%');

            // Send notification to author if status changed significantly
            if (in_array($newStatus, ['published', 'rejected']) && $oldStatus !== $newStatus) {
                $this->sendStatusChangeNotification($news, $newStatus, $data['reason'] ?? '');
            }

            // Log activity
            $this->logActivity($request->user['id'], 'news_status_changed', 'news', $newsId, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $data['reason'] ?? ''
            ]);

            return $this->successResponse([], 'News status updated successfully');
        } else {
            return $this->errorResponse('Failed to update news status');
        }
    }

    public function bulkUpdateStatus(Request $request): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getData();
        
        if (empty($data['ids']) || !is_array($data['ids']) || empty($data['status'])) {
            return $this->errorResponse('Article IDs and status are required');
        }

        $ids = array_map('intval', $data['ids']);
        $status = $data['status'];
        $reason = $data['reason'] ?? '';

        $successCount = 0;
        $failedIds = [];

        foreach ($ids as $id) {
            $news = $this->newsModel->find($id);
            if (!$news) {
                $failedIds[] = $id;
                continue;
            }

            $success = match($status) {
                'published' => $this->newsModel->approveNews($id, $request->user['id']),
                'rejected' => $this->newsModel->rejectNews($id, $request->user['id']),
                default => $this->newsModel->update($id, ['status' => $status]) !== null
            };

            if ($success) {
                $successCount++;
                
                // Send notification
                if (in_array($status, ['published', 'rejected'])) {
                    $this->sendStatusChangeNotification($news, $status, $reason);
                }

                // Log activity
                $this->logActivity($request->user['id'], 'news_bulk_status_changed', 'news', $id, [
                    'new_status' => $status,
                    'reason' => $reason
                ]);
            } else {
                $failedIds[] = $id;
            }
        }

        // Clear cache
        $this->cache->deletePattern('news_%');

        $message = "Updated {$successCount} article" . ($successCount !== 1 ? 's' : '');
        if (!empty($failedIds)) {
            $message .= ". Failed to update " . count($failedIds) . " article" . (count($failedIds) !== 1 ? 's' : '');
        }

        return $this->successResponse([
            'success_count' => $successCount,
            'failed_ids' => $failedIds
        ], $message);
    }

    public function bulkDelete(Request $request): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $data = $request->getData();
        
        if (empty($data['ids']) || !is_array($data['ids'])) {
            return $this->errorResponse('Article IDs are required');
        }

        $ids = array_map('intval', $data['ids']);
        $successCount = 0;
        $failedIds = [];

        foreach ($ids as $id) {
            if ($this->newsModel->delete($id)) {
                $successCount++;
                
                // Log activity
                $this->logActivity($request->user['id'], 'news_bulk_deleted', 'news', $id);
            } else {
                $failedIds[] = $id;
            }
        }

        // Clear cache
        $this->cache->deletePattern('news_%');

        $message = "Deleted {$successCount} article" . ($successCount !== 1 ? 's' : '');
        if (!empty($failedIds)) {
            $message .= ". Failed to delete " . count($failedIds) . " article" . (count($failedIds) !== 1 ? 's' : '');
        }

        return $this->successResponse([
            'success_count' => $successCount,
            'failed_ids' => $failedIds
        ], $message);
    }

    public function statistics(Request $request): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $cacheKey = $this->cache->generateKey('admin_news_stats');
        
        $stats = $this->cache->remember($cacheKey, function() {
            $newsStats = $this->newsModel->getNewsStatistics();
            $trending = $this->newsModel->getTrendingNews(5);
            $moderationQueue = $this->newsModel->getModerationQueue(1, 5);
            $recentNews = $this->getRecentNews(10);
            $authorStats = $this->getAuthorStatistics();
            $categoryStats = $this->getCategoryStatistics();

            return [
                'statistics' => $newsStats,
                'trending' => $trending,
                'moderation_queue' => $moderationQueue['data'],
                'recent_news' => $recentNews,
                'author_stats' => $authorStats,
                'category_stats' => $categoryStats
            ];
        }, 300); // Cache for 5 minutes

        return $this->successResponse($stats);
    }

    public function export(Request $request): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $format = $request->getQuery('format', 'csv');
        $status = $request->getQuery('status');
        $categoryId = $request->getQuery('category_id');
        $authorId = $request->getQuery('author_id');
        $dateFrom = $request->getQuery('date_from');
        $dateTo = $request->getQuery('date_to');

        try {
            $filename = $this->exportNews($format, [
                'status' => $status,
                'category_id' => $categoryId,
                'author_id' => $authorId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
            
            // Log activity
            $this->logActivity($request->user['id'], 'news_exported', 'system', 0, [
                'format' => $format,
                'filters' => array_filter([
                    'status' => $status,
                    'category_id' => $categoryId,
                    'author_id' => $authorId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ])
            ]);

            return $this->successResponse([
                'download_url' => '/api/admin/news/download/' . $filename,
                'filename' => $filename
            ], 'Export completed successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Export failed: ' . $e->getMessage());
        }
    }

    public function scheduledPublish(Request $request): Response
    {
        if ($request->user['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        try {
            $publishedCount = $this->newsModel->publishScheduledNews();
            
            if ($publishedCount > 0) {
                // Clear cache
                $this->cache->deletePattern('news_%');
                
                // Log activity
                $this->logActivity($request->user['id'], 'scheduled_news_published', 'system', 0, [
                    'published_count' => $publishedCount
                ]);
            }

            return $this->successResponse([
                'published_count' => $publishedCount
            ], "Published {$publishedCount} scheduled article" . ($publishedCount !== 1 ? 's' : ''));

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to publish scheduled articles: ' . $e->getMessage());
        }
    }

    private function sendStatusChangeNotification(array $news, string $status, string $reason = ''): void
    {
        // Get author details
        $author = $this->userModel->find($news['author_id']);
        if (!$author) return;

        $statusMessages = [
            'published' => 'Your article has been approved and published.',
            'rejected' => 'Your article has been rejected and will not be published.',
            'archived' => 'Your article has been archived.'
        ];

        $subject = match($status) {
            'published' => 'Article Published: ' . $news['title'],
            'rejected' => 'Article Rejected: ' . $news['title'],
            'archived' => 'Article Archived: ' . $news['title'],
            default => 'Article Status Updated: ' . $news['title']
        };

        $message = $statusMessages[$status] ?? 'Your article status has been updated.';
        if ($reason) {
            $message .= "\n\nReason: " . $reason;
        }

        $emailData = [
            'to_email' => $author['email'],
            'to_name' => $author['first_name'] . ' ' . $author['last_name'],
            'subject' => $subject,
            'body_html' => $this->getStatusChangeEmailTemplate($author, $news, $status, $message),
            'priority' => 'high'
        ];

        // Queue email (assuming EmailService is available)
        // $this->emailService->queue($emailData);
    }

    private function getStatusChangeEmailTemplate(array $author, array $news, string $status, string $message): string
    {
        $statusColor = match($status) {
            'published' => '#10b981',
            'rejected' => '#ef4444',
            'archived' => '#f59e0b',
            default => '#6b7280'
        };

        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: {$statusColor};'>Article Status Update</h2>
                <p>Hello {$author['first_name']},</p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                
                <div style='background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #374151;'>Article Details:</h3>
                    <p style='margin: 5px 0;'><strong>Title:</strong> " . htmlspecialchars($news['title']) . "</p>
                    <p style='margin: 5px 0;'><strong>Status:</strong> <span style='color: {$statusColor};'>" . ucfirst($status) . "</span></p>
                    <p style='margin: 5px 0;'><strong>Updated:</strong> " . date('F j, Y g:i A') . "</p>
                </div>
                
                <p>You can view and manage your articles in the <a href='" . ($_ENV['APP_URL'] ?? '') . "/admin/news' style='color: #3b82f6;'>admin dashboard</a>.</p>
                
                <p>Best regards,<br>The " . ($_ENV['APP_NAME'] ?? 'News Platform') . " Team</p>
            </div>
        </body>
        </html>";
    }

    private function getRecentNews(int $limit): array
    {
        $sql = "SELECT n.*, u.first_name, u.last_name, c.name as category_name 
                FROM news n
                LEFT JOIN users u ON n.author_id = u.id
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE n.deleted_at IS NULL
                ORDER BY n.created_at DESC 
                LIMIT ?";
        
        $results = $this->db->fetchAll($sql, [$limit]);
        return array_map([$this->newsModel, 'castAttributes'], $results);
    }

    private function getAuthorStatistics(): array
    {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.username,
                       COUNT(n.id) as article_count,
                       COUNT(CASE WHEN n.status = 'published' THEN 1 END) as published_count,
                       COUNT(CASE WHEN n.status = 'pending' THEN 1 END) as pending_count,
                       AVG(n.view_count) as avg_views
                FROM users u
                LEFT JOIN news n ON u.id = n.author_id AND n.deleted_at IS NULL
                WHERE u.role IN ('contributor', 'super_admin') AND u.deleted_at IS NULL
                GROUP BY u.id
                HAVING article_count > 0
                ORDER BY published_count DESC, article_count DESC
                LIMIT 10";
        
        return $this->db->fetchAll($sql);
    }

    private function getCategoryStatistics(): array
    {
        $sql = "SELECT c.id, c.name, c.color,
                       COUNT(n.id) as article_count,
                       COUNT(CASE WHEN n.status = 'published' THEN 1 END) as published_count,
                       AVG(n.view_count) as avg_views
                FROM categories c
                LEFT JOIN news n ON c.id = n.category_id AND n.deleted_at IS NULL
                WHERE c.deleted_at IS NULL
                GROUP BY c.id
                ORDER BY published_count DESC
                LIMIT 10";
        
        return $this->db->fetchAll($sql);
    }

    private function exportNews(string $format, array $filters): string
    {
        // Build query based on filters
        $whereConditions = ['n.deleted_at IS NULL'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $whereConditions[] = 'n.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category_id'])) {
            $whereConditions[] = 'n.category_id = ?';
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['author_id'])) {
            $whereConditions[] = 'n.author_id = ?';
            $params[] = $filters['author_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'n.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'n.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT n.id, n.title, n.slug, n.summary, n.status, n.is_featured, n.is_breaking,
                       n.view_count, n.like_count, n.comment_count, n.created_at, n.published_at,
                       u.first_name, u.last_name, u.username, u.email,
                       c.name as category_name
                FROM news n
                LEFT JOIN users u ON n.author_id = u.id
                LEFT JOIN categories c ON n.category_id = c.id
                {$whereClause}
                ORDER BY n.created_at DESC";
        
        $news = $this->db->fetchAll($sql, $params);
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "news_export_{$timestamp}.{$format}";
        $filepath = dirname(__DIR__, 3) . '/storage/exports/' . $filename;
        
        // Create exports directory if it doesn't exist
        $exportDir = dirname($filepath);
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        if ($format === 'csv') {
            $this->exportToCsv($news, $filepath);
        } else {
            throw new \InvalidArgumentException('Unsupported export format');
        }
        
        return $filename;
    }

    private function exportToCsv(array $news, string $filepath): void
    {
        $file = fopen($filepath, 'w');
        
        // Write header
        $headers = [
            'ID', 'Title', 'Slug', 'Summary', 'Status', 'Featured', 'Breaking',
            'Views', 'Likes', 'Comments', 'Author Name', 'Author Username', 'Author Email',
            'Category', 'Created At', 'Published At'
        ];
        fputcsv($file, $headers);
        
        // Write data
        foreach ($news as $article) {
            fputcsv($file, [
                $article['id'],
                $article['title'],
                $article['slug'],
                $article['summary'],
                $article['status'],
                $article['is_featured'] ? 'Yes' : 'No',
                $article['is_breaking'] ? 'Yes' : 'No',
                $article['view_count'],
                $article['like_count'],
                $article['comment_count'],
                $article['first_name'] . ' ' . $article['last_name'],
                $article['username'],
                $article['email'],
                $article['category_name'],
                $article['created_at'],
                $article['published_at']
            ]);
        }
        
        fclose($file);
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