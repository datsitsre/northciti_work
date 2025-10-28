<?php

// api/src/Controllers/Api/NewsController.php - Fixed News API Controller

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Base\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\User;
use App\Models\News;
use App\Models\Tag;
use App\Models\Media;
use App\Models\Comment;
use App\Models\Category;
use App\Models\private;
use App\Services\CacheService;
use App\Services\ModerationService;
use App\Services\EmailService;
use App\Validators\NewsValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;

class NewsController extends BaseController
{
    private News $newsModel;
    private User $userModel;
    private Tag $tagModel;
    private Media $mediaModel;
    private Comment $commentModel;
    private Category $categoryModel;
    private CacheService $cache;
    private ModerationService $moderationService;
    private EmailService $emailService;
    private NewsValidator $validator;

    public function __construct(
        Database $database,
        User $userModel, 
        News $newsModel, 
        Tag $tagModel, 
        Comment $commentModel,
        Category $categoryModel,
        Media $mediaModel,
        CacheService $cache,
        ModerationService $moderationService,
        EmailService $emailService,
        NewsValidator $validator
    ) {
        parent::__construct();
        $this->db = $database;
        $this->userModel = $userModel;
        $this->newsModel = $newsModel;
        $this->tagModel = $tagModel;
        $this->commentModel = $commentModel;
        $this->categoryModel = $categoryModel;
        $this->mediaModel = $mediaModel;
        $this->cache = $cache;
        $this->moderationService = $moderationService;
        $this->emailService = $emailService;
        $this->validator = $validator;

        // Inject dependencies into the news model for enrichment
        $this->newsModel->setTagModel($this->tagModel);
        $this->newsModel->setMediaModel($this->mediaModel);
        $this->newsModel->setCommentModel($this->commentModel);
    }

    public function increaseView(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        $news = $this->newsModel->find($newsId);
        
        if (!$news || $news['status'] !== 'published') {
            throw new NotFoundException('News not found');
        }
        
        $result = $this->newsModel->incrementViewCount(
            $news['id'], 
            $request->getUser()['id'] ?? null, 
            $request->getIp()
        );

        return $this->successResponse([], $result ? 'View increased successfully.' : 'View increase failed/already increased.');
    }

    public function index(Request $request): Response
    {
        $queryBody = $request->getBody();
        $page = (int)($request->getQuery('page') ?? $queryBody['page'] ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? $queryBody['per_page'] ?? 10), 50);
        $categoryId = $request->getQuery('category') ?? ($queryBody['category_slug'] ?? ($queryBody['category_name'] ?? null));
        $authorId = $request->getQuery('author');
        $featured = $request->getQuery('featured') === 'true';
        $userId = $request->getUser()['id'] ?? null;

        if (!empty($categoryId) && !is_numeric($categoryId)) {
            $category = $this->categoryModel->findByAny($categoryId);
            $categoryId = $category['id'] ?? null;
        }
        
        $filters = [];
        if ($categoryId) $filters['category_id'] = (int)$categoryId;
        if ($authorId) $filters['author_id'] = (int)$authorId;
        if ($featured) $filters['is_featured'] = true;

        $cacheKey = $this->cache->generateKey('news_list_enriched', $page, $perPage, $filters, $userId);
        
        $result = $this->cache->remember($cacheKey, function() use ($page, $perPage, $filters, $userId) {
            // Now passes userId and enrichment enabled by default
            return $this->newsModel->getPublished($page, $perPage, $filters, $userId, true);
        }, 300); // 5 minutes cache

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => $filters
        ]);
    }

    public function show(Request $request, string $identifier): Response
    {
        $userId = $request->getUser()['id'] ?? null;
        
        // Try to find by ID first, then by slug
        if (is_numeric($identifier)) {
            $news = $this->newsModel->find((int)$identifier);
        } else {
            $news = $this->newsModel->findBySlug($identifier);
        }
        
        if (!$news || $news['status'] !== 'published') {
            throw new NotFoundException('Article not found');
        }

        // Increment view count
        $this->newsModel->incrementViewCount(
            $news['id'], 
            $userId, 
            $request->getIp()
        );

        // Get related content manually and enrich single article
        $relatedNews = $this->newsModel->getRelated($news['id'], 6, $userId, true); // Enriched related
        $tags = $this->tagModel->getTagsByContent('news', $news['id']);
        $media = $this->mediaModel->getContentMedia('news', $news['id']);
        $commentCount = $this->commentModel->getTotalByContentId('news', $news['id']);

        // Check if user liked/bookmarked this article
        $isLiked = false;
        $isBooked = false;
        if ($userId) {
            $isLiked = $this->newsModel->isLikedByUser($news['id'], $userId);
            $isBooked = $this->newsModel->isBookedByUser($news['id'], $userId);
        }

        $response = array_merge($news, [
            'tags' => $tags,
            'media' => $media,
            'related_news' => $relatedNews,
            'is_liked' => $isLiked,
            'is_bookmarked' => $isBooked,
            'comment_count' => $commentCount
        ]);

        return $this->successResponse($response);
    }

    // Admin endpoints
    public function adminIndex(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $page = $request->getQueryInt('page', 1);
        $perPage = min($request->getQueryInt('per_page', 20), 100);
        $status = $request->getQuery('status');
        $search = $request->getQuery('search') ?? "";
        $categoryId = $request->getQuery('category_id');
        $authorId = $request->getQuery('author_id');
        $userId = $request->getUser()['id'];

        if (!empty($search)) {
            $filters = array_filter([
                'status' => $status,
                'category_id' => $categoryId,
                'author_id' => $authorId
            ]);
            $result = $this->newsModel->searchAdminNews($search, $filters, $page, $perPage, $userId, true);
        }
        elseif (!empty($categoryId)) {
            $result = $this->newsModel->searchAdminNews("", ['category_id' => $categoryId], $page, $perPage, $userId, true);
        }
        elseif (!empty($authorId)) {
            $result = $this->newsModel->searchAdminNews("", ['author_id' => $authorId], $page, $perPage, $userId, true);
        }
        elseif (!empty($status)) {
            // For status-based queries, use the getByStatus method and add enrichment manually
            $baseResult = $this->newsModel->getByStatus($status, $page, $perPage);
            // Since getByStatus doesn't support enrichment, we'll enrich manually
            $enrichedData = [];
            foreach ($baseResult['data'] as $article) {
                $tags = $this->tagModel->getTagsByContent('news', $article['id']);
                $media = $this->mediaModel->getContentMedia('news', $article['id']);
                $commentCount = $this->commentModel->getTotalByContentId('news', $article['id']);
                $isLiked = $userId ? $this->newsModel->isLikedByUser($article['id'], $userId) : false;
                $isBookmarked = $userId ? $this->newsModel->isBookedByUser($article['id'], $userId) : false;
                
                $enrichedData[] = array_merge($article, [
                    'tags' => $tags,
                    'media' => $media,
                    'related_news' => [], // Don't include related for listings
                    'is_liked' => $isLiked,
                    'is_bookmarked' => $isBookmarked,
                    'comment_count' => $commentCount
                ]);
            }
            $result = [
                'data' => $enrichedData,
                'pagination' => $baseResult['pagination']
            ];
        }
        else {
            $result = $this->newsModel->getAllNews($page, $perPage, $userId, true);
        }

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => [
                'status' => $status,
                'search' => $search,
                'category' => $categoryId,
                'author' => $authorId
            ]
        ]);
    }

    public function adminShow(Request $request, string $identifier): Response
    {
        $userId = $request->getUser()['id'] ?? null;
        
        // Try to find by ID first, then by slug
        if (is_numeric($identifier)) {
            $news = $this->newsModel->find((int)$identifier);
        } else {
            $news = $this->newsModel->findBySlug($identifier);
        }
        
        if (!$news) {
            throw new NotFoundException('Article not found');
        }

        // Get related content and enrich manually for admin show
        $relatedNews = $this->newsModel->getRelated($news['id'], 4, $userId, true);
        $tags = $this->tagModel->getTagsByContent('news', $news['id']);
        $media = $this->mediaModel->getContentMedia('news', $news['id']);
        $commentCount = $this->commentModel->getTotalByContentId('news', $news['id']);

        // Check if user liked/bookmarked this article
        $isLiked = false;
        $isBooked = false;
        if ($userId) {
            $isLiked = $this->newsModel->isLikedByUser($news['id'], $userId);
            $isBooked = $this->newsModel->isBookedByUser($news['id'], $userId);
        }

        $response = array_merge($news, [
            'tags' => $tags,
            'media' => $media,
            'related_news' => $relatedNews,
            'is_liked' => $isLiked,
            'is_bookmarked' => $isBooked,
            'comment_count' => $commentCount
        ]);

        return $this->successResponse($response);
    }

    public function featured(Request $request): Response
    {
        $limit = min((int)($request->getQuery('limit') ?? 5), 20);
        $userId = $request->getUser()['id'] ?? null;
        
        $cacheKey = $this->cache->generateKey('news_featured_enriched', $limit, $userId);
        
        $featuredNews = $this->cache->remember($cacheKey, function() use ($limit, $userId) {
            return $this->newsModel->getFeatured($limit, $userId, true); // Enriched
        }, 600); // 10 minutes cache

        return $this->successResponse($featuredNews);
    }

    public function breaking(Request $request): Response
    {
        $limit = min((int)($request->getQuery('limit') ?? 3), 10);
        $userId = $request->getUser()['id'] ?? null;
        
        $cacheKey = $this->cache->generateKey('news_breaking_enriched', $limit, $userId);
        
        $breakingNews = $this->cache->remember($cacheKey, function() use ($limit, $userId) {
            return $this->newsModel->getBreaking($limit, $userId, true); // Enriched
        }, 180); // 3 minutes cache for breaking news

        return $this->successResponse($breakingNews);
    }

    public function trending(Request $request): Response
    {
        $days = min((int)($request->getQuery('days') ?? 7), 30);
        $limit = min((int)($request->getQuery('limit') ?? 10), 50);
        $userId = $request->getUser()['id'] ?? null;
        
        $cacheKey = $this->cache->generateKey('news_trending_enriched', $days, $limit, $userId);
        
        $trendingNews = $this->cache->remember($cacheKey, function() use ($days, $limit, $userId) {
            return $this->newsModel->getTrending($days, $limit, $userId, true); // Enriched
        }, 300); // 5 minutes cache

        return $this->successResponse($trendingNews);
    }

    public function byCategory(Request $request, string $categorySlug): Response
    {
        // Get category by slug
        $categorySql = "SELECT id, name FROM categories WHERE slug = ? AND is_active = 1 AND deleted_at IS NULL";
        $category = $this->db->fetch($categorySql, [$categorySlug]);
        
        if (!$category) {
            throw new NotFoundException('Category not found');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        $userId = $request->getUser()['id'] ?? null;

        $result = $this->newsModel->getByCategory($category['id'], $page, $perPage, $userId, true); // Enriched

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'category' => $category
        ]);
    }

    public function byTag(Request $request, string $tagSlug): Response
    {
        $tag = $this->tagModel->findBySlug($tagSlug);
        
        if (!$tag) {
            throw new NotFoundException('Tag not found');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        $userId = $request->getUser()['id'] ?? null;

        // Get base result from tag model
        $baseResult = $this->tagModel->getContentByTag($tag['id'], 'news', $page, $perPage);
        
        // Manually enrich the data since tagModel doesn't support enrichment
        $enrichedData = [];
        foreach ($baseResult['data'] as $article) {
            $tags = $this->tagModel->getTagsByContent('news', $article['id']);
            $media = $this->mediaModel->getContentMedia('news', $article['id']);
            $commentCount = $this->commentModel->getTotalByContentId('news', $article['id']);
            $isLiked = $userId ? $this->newsModel->isLikedByUser($article['id'], $userId) : false;
            $isBookmarked = $userId ? $this->newsModel->isBookedByUser($article['id'], $userId) : false;
            
            $enrichedData[] = array_merge($article, [
                'tags' => $tags,
                'media' => $media,
                'related_news' => [], // Don't include related for listings
                'is_liked' => $isLiked,
                'is_bookmarked' => $isBookmarked,
                'comment_count' => $commentCount
            ]);
        }
        
        $result = [
            'data' => $enrichedData,
            'pagination' => $baseResult['pagination']
        ];

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'tag' => $tag
        ]);
    }

    public function byTagId(Request $request, string $tagId): Response
    {
        $tag = $this->tagModel->findById((int)$tagId);

        if (!$tag) {
            throw new NotFoundException('Tag not found');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        $userId = $request->getUser()['id'] ?? null;

        // Get base result from tag model
        $baseResult = $this->tagModel->getContentByTag($tag['id'], 'news', $page, $perPage);
        
        // Manually enrich the data
        $enrichedData = [];
        foreach ($baseResult['data'] as $article) {
            $tags = $this->tagModel->getTagsByContent('news', $article['id']);
            $media = $this->mediaModel->getContentMedia('news', $article['id']);
            $commentCount = $this->commentModel->getTotalByContentId('news', $article['id']);
            $isLiked = $userId ? $this->newsModel->isLikedByUser($article['id'], $userId) : false;
            $isBookmarked = $userId ? $this->newsModel->isBookedByUser($article['id'], $userId) : false;
            
            $enrichedData[] = array_merge($article, [
                'tags' => $tags,
                'media' => $media,
                'related_news' => [], // Don't include related for listings
                'is_liked' => $isLiked,
                'is_bookmarked' => $isBookmarked,
                'comment_count' => $commentCount
            ]);
        }
        
        $result = [
            'data' => $enrichedData,
            'pagination' => $baseResult['pagination']
        ];

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'tag' => $tag
        ]);
    }

    public function search(Request $request): Response
    {
        $query = trim($request->getQuery('q') ?? $request->getQuery('query') ?? '');
        
        if (empty($query)) {
            return $this->errorResponse('Search query is required');
        }

        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        $categoryId = $request->getQuery('category_id');
        $authorId = $request->getQuery('author_id');
        $dateFrom = $request->getQuery('date_from');
        $dateTo = $request->getQuery('date_to');
        $userId = $request->getUser()['id'] ?? null;

        $filters = array_filter([
            'category_id' => $categoryId,
            'author_id' => $authorId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        $result = $this->newsModel->searchNews($query, $filters, $page, $perPage, $userId, true); // Enriched

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'query' => $query,
            'filters' => $filters
        ]);
    }

    public function myNews(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        $page = (int)($request->getQuery('page') ?? 1);
        $perPage = min((int)($request->getQuery('per_page') ?? 10), 50);
        $status = $request->getQuery('status') ?? 'all';

        $result = $this->newsModel->getByAuthor($userId, $page, $perPage, $status, $userId, true); // Enriched

        return $this->paginatedResponse($result['data'], $result['pagination'], [
            'filters' => ['status' => $status]
        ]);
    }

    public function like(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        $news = $this->newsModel->find($newsId);
        
        if (!$news || $news['status'] !== 'published') {
            throw new NotFoundException('Article not found');
        }

        $isLiked = $this->newsModel->toggleLike($newsId, $request->getUser()['id'], $request->getIp());

        // Log activity
        $action = $isLiked ? 'news_liked' : 'news_unliked';
        $this->logActivity($request->getUser()['id'], $action, 'news', $newsId);

        return $this->successResponse([
            'is_liked' => $isLiked,
            'like_count' => $this->newsModel->find($newsId)['like_count']
        ], $isLiked ? 'Article liked' : 'Article unliked');
    }

    /**
     * Check if user has liked this news article
     */
    public function likeStatus(Request $request, string $id): Response
    {
        $user = $request->getUser() ?? null;
        
        if (!$user) {
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_liked' => false,
                    'like_count' => 0
                ]
            ]);
        }
        
        try {
            
            $isLiked = $this->userModel->isContentLiked($user['id'], 'news', (int)$id);
            $likeCount = $this->userModel->getContentLikeCount('news', (int)$id);
            
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_liked' => $isLiked,
                    'like_count' => $likeCount
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Error checking like status: " . $e->getMessage());
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to check like status'
            ], 500);
        }
    }

    public function bookmark(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        
        $news = $this->newsModel->find($newsId);
        if (!$news || $news['status'] !== 'published') {
            throw new NotFoundException('Article not found');
        }

        // Check if already bookmarked
        $isBookmarked = $this->newsModel->toggleBookmark($newsId, $request->getUser()['id'], $request->getIp());

        // Log activity
        $action = $isBookmarked ? 'news_bookmarked' : 'news_unbookmarked';
        $this->logActivity($request->getUser()['id'], $action, 'news', $newsId);

        return $this->successResponse([
            'is_bookmarked' => $isBookmarked,
            'bookmark_count' => $this->newsModel->find($newsId)['bookmark_count']
        ], $isBookmarked ? 'Article bookmarked' : 'Article removed from bookmark');
    }

    /**
     * Check if user has bookmarked this news article
     */
    public function bookmarkStatus(Request $request, string $id): Response
    {
        $user = $request->getUser() ?? null;
        
        if (!$user) {
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_bookmarked' => false,
                    'bookmark_count' => 0
                ]
            ]);
        }
        
        try {
            $isBookmarked = $this->userModel->isContentBookmarked($user['id'], 'news', (int)$id);
            $bookmarkCount = $this->userModel->getContentBookmarkCount('news', (int)$id);
            
            return $this->response->json([
                'success' => true,
                'data' => [
                    'is_bookmarked' => $isBookmarked,
                    'bookmark_count' => $bookmarkCount
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Error checking bookmark status: " . $e->getMessage());
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to check bookmark status'
            ], 500);
        }
    }


    public function create(Request $request): Response
    {
        $userId = $request->getUser()['id'];
        
        // Get all data from request (handles multipart/form-data)
        $data = $request->getAllData();
        
        // Handle featured image upload
        $files = $request->getFiles();
        
        // Check for featured image in multiple possible locations
        $featuredImageFile = null;
        if (!empty($files['featured_image']) && $files['featured_image']['error'] === UPLOAD_ERR_OK) {
            $featuredImageFile = $files['featured_image'];
        } elseif (!empty($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $featuredImageFile = $_FILES['featured_image'];
        }

        // Process the featured image if present
        if ($featuredImageFile) {
            try {
                $uploadedImagePath = $this->handleFeaturedImageUpload($featuredImageFile);
                if ($uploadedImagePath) {
                    $data['featured_image'] = $uploadedImagePath;
                }
            } catch (\Exception $e) {
                error_log('Featured image upload failed: ' . $e->getMessage());
                return $this->errorResponse('Failed to upload featured image: ' . $e->getMessage());
            }
        }

        // Format datetime fields
        if (isset($data['scheduled_at']) && !empty($data['scheduled_at'])) {
            $data['scheduled_at'] = $this->formatDatetime($data['scheduled_at']);
        }
        if (isset($data['published_at']) && !empty($data['published_at'])) {
            $data['published_at'] = $this->formatDatetime($data['published_at']);
        }

        // Handle boolean fields - convert various representations to 1/0
        $booleanFields = ['is_featured', 'is_breaking', 'is_fact_checked'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->convertToBoolean($data[$field]) ? 1 : 0;
            } else {
                $data[$field] = 0;
            }
        }

        // Validate input data
        $validation = $this->validator->validateNews($data);
        if (!$validation['valid']) {
            // Clean up uploaded image if validation fails
            if (!empty($uploadedImagePath)) {
                $this->deleteUploadedFile($uploadedImagePath);
            }
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Set author and initial status
        $data['author_id'] = $userId;
        $data['status'] = 'draft'; // Default status, may change based on moderation rules
        
        // Create the news article
        try {
            $news = $this->newsModel->createNews($data);
            
            if (!$news) {
                // Clean up uploaded image if creation fails
                if (!empty($uploadedImagePath)) {
                    $this->deleteUploadedFile($uploadedImagePath);
                }
                return $this->errorResponse('Failed to create news article');
            }
        } catch (\Exception $e) {
            // Clean up uploaded image if creation fails
            if (!empty($uploadedImagePath)) {
                $this->deleteUploadedFile($uploadedImagePath);
            }
            error_log('News creation failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to create news article: ' . $e->getMessage());
        }

        // Add to moderation queue using the unified service
        $moderationOptions = [
            'is_breaking_news' => $data['is_breaking'] ?? false,
            'has_media' => !empty($data['featured_image']),
            'word_count' => str_word_count($data['content'] ?? ''),
            'has_external_links' => $this->hasExternalLinks($data['content'] ?? '')
        ];
        
        try {
            $queueAdded = $this->moderationService->addToModerationQueue('news', $news['id'], $userId, 'medium');
            
            // Update status based on moderation result
            if ($queueAdded) {
                $this->newsModel->update($news['id'], ['status' => 'pending']);
                $news['status'] = 'pending';
            }
        } catch (\Exception $e) {
            error_log('Failed to add news to moderation queue: ' . $e->getMessage());
            // Continue without failing - the news was created successfully
        }

        // Log activity
        $this->logActivity($userId, 'news_created', 'news', $news['id'], [
            'status' => $news['status'],
            'requires_moderation' => $queueAdded ?? false,
            'has_featured_image' => !empty($data['featured_image'])
        ]);

        // Clear cache
        $this->cache->deletePattern('news_*');

        $message = $news['status'] === 'published' ? 
            'News article published successfully' : 
            'News article created and submitted for review';

        return $this->successResponse($news, $message, 201);
    }

    /**
     * Update an existing news article
     */
    public function update(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        $userId = $request->getUser()['id'];
        
        // Get existing news article
        $news = $this->newsModel->find($newsId);
        if (!$news) {
            throw new NotFoundException('News article not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $news['author_id'] !== $userId) {
            throw new AuthorizationException('Access denied');
        }

        // Get all data from request
        $data = $request->getAllData();
        
        // Remove method override if present
        unset($data['_method']);
        
        // Handle featured image upload
        $files = $request->getFiles();
        $featuredImageFile = null;
        
        if (!empty($files['featured_image']) && $files['featured_image']['error'] === UPLOAD_ERR_OK) {
            $featuredImageFile = $files['featured_image'];
        } elseif (!empty($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $featuredImageFile = $_FILES['featured_image'];
        }
        
        // Process new featured image if uploaded
        if ($featuredImageFile) {
            try {
                $uploadedImagePath = $this->handleFeaturedImageUpload($featuredImageFile);
                if ($uploadedImagePath) {
                    // Delete old image if it exists
                    if (!empty($news['featured_image'])) {
                        $this->deleteUploadedFile($news['featured_image']);
                    }
                    $data['featured_image'] = $uploadedImagePath;
                }
            } catch (\Exception $e) {
                error_log('Featured image upload failed: ' . $e->getMessage());
                return $this->errorResponse('Failed to upload featured image: ' . $e->getMessage());
            }
        }

        // Format datetime fields
        if (isset($data['scheduled_at']) && !empty($data['scheduled_at'])) {
            $data['scheduled_at'] = $this->formatDatetime($data['scheduled_at']);
        }
        if (isset($data['published_at']) && !empty($data['published_at'])) {
            $data['published_at'] = $this->formatDatetime($data['published_at']);
        }

        // Handle boolean fields
        $booleanFields = ['is_featured', 'is_breaking', 'is_fact_checked'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->convertToBoolean($data[$field]) ? 1 : 0;
            }
        }
        
        // Validate input data
        $validation = $this->validator->validateNews($data, $newsId);
        if (!$validation['valid']) {
            // Clean up new uploaded image if validation fails
            if (!empty($uploadedImagePath)) {
                $this->deleteUploadedFile($uploadedImagePath);
            }
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        // Check for significant changes that require re-moderation
        $significantFields = ['title', 'content', 'summary'];
        $hasSignificantChanges = false;
        
        foreach ($significantFields as $field) {
            if (isset($data[$field]) && $data[$field] !== $news[$field]) {
                $hasSignificantChanges = true;
                break;
            }
        }

        // Reset to pending if significant changes and currently published
        if ($hasSignificantChanges && $news['status'] === 'published') {
            $data['status'] = 'pending';
        }

        // Update the news article
        try {
            $updatedNews = $this->newsModel->updateNews($newsId, $data);
            
            if (!$updatedNews) {
                // Clean up new uploaded image if update fails
                if (!empty($uploadedImagePath)) {
                    $this->deleteUploadedFile($uploadedImagePath);
                }
                return $this->errorResponse('Failed to update news article');
            }
        } catch (\Exception $e) {
            // Clean up new uploaded image if update fails
            if (!empty($uploadedImagePath)) {
                $this->deleteUploadedFile($uploadedImagePath);
            }
            error_log('News update failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to update news article: ' . $e->getMessage());
        }

        // Clear cache
        $this->cache->deletePattern('news_*');

        // Re-add to moderation queue if needed
        if ($hasSignificantChanges && $news['status'] === 'published') {
            try {
                $this->moderationService->addToModerationQueue('news', $newsId, $userId, 'medium');
                
                // Send notification about re-moderation via email
                if (!empty($updatedNews['author_email'])) {
                    $this->emailService->queue([
                        'to_email' => $updatedNews['author_email'],
                        'subject' => 'Article Updated - Under Review',
                        'body_html' => "Your article '{$updatedNews['title']}' has been updated and is now under review.",
                        'priority' => 'normal'
                    ]);
                }
            } catch (\Exception $e) {
                error_log('Failed to add updated news to moderation queue: ' . $e->getMessage());
                // Continue without failing
            }
        }

        // Log activity
        $this->logActivity($userId, 'news_updated', 'news', $newsId, [
            'requires_remoderation' => $hasSignificantChanges,
            'updated_fields' => array_keys($data)
        ]);

        $message = $hasSignificantChanges && $news['status'] === 'published' ? 
            'News article updated and submitted for re-review' : 
            'News article updated successfully';

        return $this->successResponse($updatedNews, $message);
    }

    public function delete(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        $news = $this->newsModel->find($newsId);
        
        if (!$news) {
            throw new NotFoundException('Article not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $news['author_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        $deleted = $this->newsModel->delete($newsId);
        
        if ($deleted) {
            // Clear cache
            $this->cache->deletePattern('news_*');

            // Log activity
            $this->logActivity($request->getUser()['id'], 'news_deleted', 'news', $newsId);

            return $this->successResponse([], 'Article deleted successfully');
        } else {
            return $this->errorResponse('Failed to delete article');
        }
    }

    public function updateStatus(Request $request, string $id): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $newsId = (int)$id;
        $data = $request->getAllData();
        
        if (empty($data['status'])) {
            return $this->errorResponse('Status is required');
        }

        $news = $this->newsModel->find($newsId);
        if (!$news) {
            throw new NotFoundException('News article not found');
        }

        $success = match($data['status']) {
            'published' => $this->newsModel->approveNews($newsId, $request->getUser()['id']),
            'rejected' => $this->newsModel->rejectNews($newsId, $request->getUser()['id']),
            default => $this->newsModel->update($newsId, ['status' => $data['status']]) !== null
        };

        if ($success) {
            // Clear cache
            $this->cache->deletePattern('news_*');

            // Log activity
            $this->logActivity($request->getUser()['id'], 'news_status_changed', 'news', $newsId, [
                'old_status' => $news['status'],
                'new_status' => $data['status']
            ]);

            return $this->successResponse([], 'News status updated successfully');
        } else {
            return $this->errorResponse('Failed to update news status');
        }
    }

    public function flag(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        $data = $request->getAllData();

        $news = $this->newsModel->find($newsId);
        if (!$news) {
            throw new NotFoundException('News article not found');
        }

        // Validate flag data
        $validation = $this->validateFlagData($data);
        if (!$validation['valid']) {
            throw new ValidationException('Validation failed', $validation['errors']);
        }

        $flagData = [
            'reporter_id' => $request->getUser()['id'] ?? null,
            'reporter_email' => $request->getUser()['email'] ?? $data['reporter_email'] ?? null,
            'flag_type' => $data['flag_type'],
            'reason' => $data['reason'],
            'ip_address' => $request->getIp()
        ];

        // Add flag using the unified moderation service
        try {
            $flagged = $this->moderationService->flagContent('news', $newsId, $flagData['reporter_id'], $flagData['flag_type'], $flagData['reason'], $flagData['reporter_email']);
            
            if ($flagged) {
                // Log activity
                if (isset($request->getUser()['id'])) {
                    $this->logActivity($request->getUser()['id'], 'news_flagged', 'news', $newsId, [
                        'flag_type' => $data['flag_type'],
                        'reason' => $data['reason']
                    ]);
                }

                return $this->successResponse([], 'Content flagged for review');
            } else {
                return $this->errorResponse('Failed to flag content');
            }
        } catch (\Exception $e) {
            error_log('Failed to flag news: ' . $e->getMessage());
            return $this->errorResponse('Failed to flag content');
        }
    }

    public function requestPublish(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        $news = $this->newsModel->find($newsId);
        
        if (!$news) {
            throw new NotFoundException('Article not found');
        }

        // Check permissions
        if ($news['author_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        if ($news['status'] !== 'draft') {
            return $this->errorResponse('Only draft articles can be submitted for review');
        }

        $updated = $this->newsModel->update($newsId, ['status' => 'pending']);
        
        if ($updated) {
            // Add to moderation queue
            try {
                $this->moderationService->addToModerationQueue('news', $newsId, $request->getUser()['id'], 'medium');
            } catch (\Exception $e) {
                error_log('Failed to add news to moderation queue: ' . $e->getMessage());
            }

            // Clear cache
            $this->cache->deletePattern('news_*');

            // Log activity
            $this->logActivity($request->getUser()['id'], 'news_submitted_for_review', 'news', $newsId);

            return $this->successResponse([], 'Article submitted for review');
        } else {
            return $this->errorResponse('Failed to submit article');
        }
    }

    public function statistics(Request $request): Response
    {
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }

        $days = min((int)($request->getQuery('days') ?? 7), 30);
        $limit = min((int)($request->getQuery('limit') ?? 10), 50);

        $stats = $this->newsModel->getNewsStatistics();
        $trending = $this->newsModel->getTrending($days, $limit);
        $moderationQueue = $this->moderationService->getModerationQueue(1, 5, 'news', null, 'pending');

        return $this->successResponse([
            'statistics' => $stats,
            'trending' => $trending,
            'moderation_queue' => $moderationQueue['data']
        ]);
    }

    public function analytics(Request $request, string $id): Response
    {
        $newsId = (int)$id;
        $news = $this->newsModel->find($newsId);
        
        if (!$news) {
            throw new NotFoundException('Article not found');
        }

        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin' && $news['author_id'] !== $request->getUser()['id']) {
            throw new AuthorizationException('Access denied');
        }

        $analytics = $this->newsModel->getNewsAnalytics($newsId);

        return $this->successResponse($analytics);
    }

    /**
     * Get analytics data for admin dashboard
     */
    public function getAnalytics(Request $request): Response
    {
        // Check permissions
        if ($request->getUser()['role'] !== 'super_admin') {
            throw new AuthorizationException('Admin access required');
        }
        
        // Get date range from request
        $startDate = $request->getQuery('start_date') ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $request->getQuery('end_date') ?? date('Y-m-d');
        
        // Validate dates
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            return $this->errorResponse('Invalid date format');
        }
        
        if (strtotime($startDate) > strtotime($endDate)) {
            return $this->errorResponse('Start date must be before end date');
        }
        
        // Get analytics data
        $analytics = $this->newsModel->getAnalytics($startDate, $endDate);
        
        return $this->successResponse($analytics);
    }

    // Private helper methods
    private function hasExternalLinks(string $content): bool
    {
        return preg_match('/https?:\/\/[^\s]+/', $content) > 0;
    }

    private function validateFlagData(array $data): array
    {
        $errors = [];

        $validFlagTypes = ['spam', 'inappropriate', 'copyright', 'misinformation', 'hate_speech', 'other'];
        
        if (empty($data['flag_type'])) {
            $errors['flag_type'] = 'Flag type is required';
        } elseif (!in_array($data['flag_type'], $validFlagTypes)) {
            $errors['flag_type'] = 'Invalid flag type';
        }

        if (empty($data['reason'])) {
            $errors['reason'] = 'Reason is required';
        } elseif (strlen($data['reason']) < 10) {
            $errors['reason'] = 'Reason must be at least 10 characters';
        } elseif (strlen($data['reason']) > 1000) {
            $errors['reason'] = 'Reason must not exceed 1000 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Format datetime string to MySQL format
     */
    private function formatDatetime($datetime): ?string
    {
        if (empty($datetime)) {
            return null;
        }

        // Handle different datetime formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
            'm/d/Y H:i:s',
            'm-d-Y H:i:s'
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $datetime);
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Try to parse with strtotime as fallback
        $timestamp = strtotime($datetime);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        error_log('Failed to parse datetime: ' . $datetime);
        return null;
    }

    private function handleFeaturedImageUpload(array $file): ?string
    {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/tiff', 'image/avif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new ValidationException('Invalid file type. Only JPEG, PNG, GIF, TIFF, AVIF, and WebP images are allowed.');
        }

        if ($file['size'] > $maxSize) {
            throw new ValidationException('File size exceeds maximum allowed size of 10MB.');
        }

        if (strpos($file['tmp_name'], '..') !== false || strpos($file['tmp_name'], '//') !== false) {
            throw new ValidationException('Invalid image path.');
        }

        // Verify it's actually an image
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new ValidationException('Invalid image file.');
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'news_' . uniqid() . '_' . time() . '.' . $extension;
        
        // Define upload directory with year/month structure
        $baseUploadDir = dirname(__DIR__, 4) . '/storage/uploads/news/';
        $yearMonth = date('Y/m');
        $fullUploadDir = $baseUploadDir . $yearMonth . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($fullUploadDir)) {
            if (!mkdir($fullUploadDir, 0755, true)) {
                throw new \RuntimeException('Failed to create upload directory');
            }
        }

        $uploadPath = $fullUploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            error_log('Failed to move uploaded file from ' . $file['tmp_name'] . ' to ' . $uploadPath);
            throw new \RuntimeException('Failed to save uploaded file');
        }

        // Return relative path for database storage
        return 'news/' . $yearMonth . '/' . $filename;
    }

    /**
     * Delete uploaded file
     */
    private function deleteUploadedFile(string $filePath): void
    {
        if (empty($filePath)) {
            return;
        }

        // Construct full path
        $fullPath = dirname(__DIR__, 4) . '/storage/uploads/' . $filePath;
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            if (!unlink($fullPath)) {
                error_log('Failed to delete file: ' . $fullPath);
            }
        }
    }

    /**
     * Convert various boolean representations to boolean
     */
    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (int)$value === 1;
        }
        
        if (is_string($value)) {
            $value = strtolower($value);
            return in_array($value, ['1', 'true', 'on', 'yes'], true);
        }
        
        return false;
    }

    /**
     * Validate date format
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Log activity helper
     */
    private function logActivity(int $userId, string $action, string $targetType, int $targetId, array $metadata = []): void
    {
        $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        try {
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
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}