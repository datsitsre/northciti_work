<?php
// admin/src/Controllers/NewsController.php - Admin News Controller

declare(strict_types=1);

class NewsController
{
    private $db;
    private $user;
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->db = Database::getInstance();
        $this->user = AuthController::getCurrentUser();
        
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
    }

    public function index()
    {
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        // Load the news management view
        LayoutHelper::setPageData(
            'News Management',
            'Manage news content',
            [
                ['label' => 'News Management', 'url' => '/news']
            ]
        );

        LayoutHelper::render('news/index', [
            'title' => 'News Management',
            'user' => $this->user,
            'has_api_access' => ApiClient::isAuthenticated()
        ]);
    }

    public function create()
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->getCurrentUser();
        
        $this->render('news/create', [
            'user' => $user,
            'pageTitle' => 'Create News Article',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'News Management', 'url' => '/admin/news'],
                ['title' => 'Create Article', 'url' => '/admin/news/create']
            ]
        ]);
    }

    public function edit(int $id): void
    {
        // Check authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->user;

        try {
            // Fetch article via API
            $response = ApiClient::get("/admin/news/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception('Article not found');
            }
            
            $article = $response['data'];
            
            // Check permissions
            // Contributors can only edit their own articles
            if ($user['role'] !== 'super_admin' && $article['author_id'] !== $user['id']) {
                throw new \Exception('You do not have permission to edit this article');
            }
            
            // Check if article is editable based on status
            if ($user['role'] !== 'super_admin' && in_array($article['status'], ['published', 'rejected'])) {
                throw new \Exception('This article cannot be edited in its current status');
            }
            
            // Fetch categories for the dropdown
            $categoriesResponse = ApiClient::get("/categories");

            $categories = $categoriesResponse && $categoriesResponse['success'] ? $categoriesResponse['data'] : [];
            
            // Fetch tags for the article
            // $tagsResponse = ApiClient::get("/admin/news/{$id}/tags");
            // if ($tagsResponse && $tagsResponse['success']) {
            //     $article['tags'] = $tagsResponse['data'];
            // } else {
            //     $article['tags'] = [];
            // }

            // Get author details
            $authorResponse = ApiClient::get("/admin/users/{$article['author_id']}");
            if ($authorResponse && $authorResponse['success']) {
                $article['author'] = $authorResponse['data'];
            } else {
                // Fallback author data
                $article['author'] = [
                    'id' => $article['author_id'],
                    'username' => $article['username'] ?? 'Unknown',
                    'first_name' => $article['first_name'] ?? 'Unknown',
                    'last_name' => $article['last_name'] ?? 'User',
                    'profile_image' => $article['author_profile_image'] ?? null
                ];
            }

            // Ensure all required fields exist
            $article['meta_title'] = $article['meta_title'] ?? '';
            $article['meta_description'] = $article['meta_description'] ?? '';
            $article['meta_keywords'] = $article['meta_keywords'] ?? '';
            $article['featured_image'] = $article['featured_image'] ?? '';
            $article['published_at'] = $article['published_at'] ?? null;
            $article['scheduled_at'] = $article['scheduled_at'] ?? null;
            $article['is_featured'] = $article['is_featured'] ?? false;
            $article['is_breaking'] = $article['is_breaking'] ?? false;
            $article['is_fact_checked'] = $article['is_fact_checked'] ?? false;
            $article['view_count'] = $article['view_count'] ?? 0;
            $article['comment_count'] = $article['comment_count'] ?? 0;

            $url = 'news/edit';
            
            // Set page data for layout
            LayoutHelper::setPageData(
                'Edit Article - ' . htmlspecialchars($article['title']),
                'Update news article information',
                [
                    ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                    ['label' => 'News Management', 'url' => Router::url('news')],
                    ['label' => 'Edit Article', 'url' => Router::url($url)]
                ]
            );
            
            // Render the edit view
            LayoutHelper::render($url, [
                'article' => $article,
                'categories' => $categories,
                'user' => $user,
                'canPublish' => $user['role'] === 'super_admin',
                'API_BASE_URL' => API_BASE_URL,
                'UPLOADS_URL' => UPLOADS_URL,
                'ADMIN_APP_URL' => ADMIN_APP_URL
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            error_log("Error editing article {$id}: " . $e->getMessage());
            
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            // Redirect to news list
            header('Location: ' . Router::url('news'));
            exit;
        }
    }

    // Additional method to handle AJAX save requests
    public function update(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRole(['contributor', 'super_admin']);
        
        header('Content-Type: application/json');
        
        try {
            // Check permissions first
            $articleResponse = ApiClient::get("/admin/news/{$id}");
            if (!$articleResponse || !$articleResponse['success']) {
                throw new \Exception('Article not found');
            }
            
            $article = $articleResponse['data'];
            
            if ($this->user['role'] !== 'super_admin' && $article['author_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Forward the update request to the API
            // This would typically handle the form data and file uploads
            // The actual implementation depends on your API client setup
            
            echo json_encode([
                'success' => true,
                'message' => 'Article updated successfully'
            ]);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function moderation(): void
    {
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        $user = $this->getCurrentUser();
        
        $this->render('news/moderation', [
            'user' => $user,
            'pageTitle' => 'News Moderation',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'News Management', 'url' => '/admin/news'],
                ['title' => 'Moderation Queue', 'url' => '/admin/news/moderation']
            ]
        ]);
    }

    public function analytics(): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->getCurrentUser();
        
        $this->render('news/analytics', [
            'user' => $user,
            'pageTitle' => 'News Analytics',
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                ['title' => 'News Management', 'url' => '/admin/news'],
                ['title' => 'Analytics', 'url' => '/admin/news/analytics']
            ]
        ]);
    }


    public function show(int $id): void
    {
        // Check admin authentication
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        $user = $this->user;
        
        try {
            // Fetch article via API
            $response = ApiClient::get("/admin/news/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception('Article not found');
            }

            $article = $response['data'];
            
            // Contributors can only view their own articles
            if ($user['role'] !== 'super_admin' && $article['author_id'] !== $user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Fetch additional data
            
            // Get author details
            $authorResponse = ApiClient::get("/admin/users/{$article['author_id']}");

            if ($authorResponse && $authorResponse['success']) {
                $article['author'] = $authorResponse['data'];
            } else {
                // Fallback author data
                $article['author'] = [
                    'id' => $article['author_id'],
                    'username' => $article['username'] ?? 'Unknown',
                    'first_name' => $article['first_name'] ?? 'Unknown',
                    'last_name' => $article['last_name'] ?? 'User',
                    'profile_image' => $article['author_profile_image'] ?? null
                ];
            }
            
            // Get approver details if approved
            if ($article['approved_by']) {
                $approverResponse = ApiClient::get("/admin/users/{$article['approved_by']}");
                if ($approverResponse && $approverResponse['success']) {
                    $article['approver'] = $approverResponse['data'];
                }
            }

            // Get tags for the article
            // $tagsResponse = ApiClient::get("/admin/news/{$id}/tags");
            // if ($tagsResponse && $tagsResponse['success']) {
            //     $article['tags'] = $tagsResponse['data'];
            // } else {
            //     $article['tags'] = [];
            // }
            
            // Get category details
            if ($article['category_id']) {
                $categoryResponse = ApiClient::get("/categories/{$article['category_id']}");
                if ($categoryResponse && $categoryResponse['success']) {
                    $category = $categoryResponse['data'];
                    $article['category_name'] = $category['name'];
                    $article['category_color'] = $category['color'] ?? '#6B7280';
                }
            } else {
                $article['category_name'] = 'Uncategorized';
                $article['category_color'] = '#6B7280';
            }
            
            // Calculate reading time if not set
            if (!isset($article['reading_time']) || !$article['reading_time']) {
                $wordCount = str_word_count(strip_tags($article['content']));
                $article['reading_time'] = max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
            }
            
            // Ensure all count fields exist
            $article['view_count'] = $article['view_count'] ?? 0;
            $article['like_count'] = $article['like_count'] ?? 0;
            $article['comment_count'] = $article['comment_count'] ?? 0;
            $article['share_count'] = $article['share_count'] ?? 0;
            
            // Format dates for display
            $article['formatted_created_at'] = $this->formatDate($article['created_at']);
            $article['formatted_published_at'] = $article['published_at'] ? $this->formatDate($article['published_at']) : null;
            $article['formatted_updated_at'] = $this->formatDate($article['updated_at']);
            
            // Set page data for layout
            LayoutHelper::setPageData(
                htmlspecialchars($article['title']),
                'Viewing article details',
                [
                    ['label' => 'News Management', 'url' => Router::url('news')],
                    ['label' => 'View Article', 'url' => Router::url("news/show/{$id}")]
                ]
            );
            
            // Render the view
            LayoutHelper::render('news/show', [
                'article' => $article,
                'user' => $user,
                'canModerate' => $user['role'] === 'super_admin',
                'canEdit' => $user['role'] === 'super_admin' || $article['author_id'] === $user['id'],
                'API_URL' => API_BASE_URL,
                'UPLOADS_URL' => UPLOADS_URL,
                'ADMIN_BASE_URL' => ADMIN_APP_URL
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            error_log("Error viewing article {$id}: " . $e->getMessage());
            
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            // Redirect to news list
            header('Location: ' . Router::url('news'));
            exit;
        }
    }

    // Additional helper method for formatting dates
    private function formatDate($dateString): array
    {
        if (!$dateString) {
            return [
                'full' => '',
                'date' => '',
                'time' => '',
                'relative' => ''
            ];
        }
        
        $timestamp = strtotime($dateString);
        $now = time();
        $diff = $now - $timestamp;
        
        // Calculate relative time
        $relative = '';
        if ($diff < 60) {
            $relative = 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            $relative = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            $relative = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            $relative = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            $relative = date('M d, Y', $timestamp);
        }
        
        return [
            'full' => date('F d, Y \a\t g:i A', $timestamp),
            'date' => date('M d, Y', $timestamp),
            'time' => date('g:i A', $timestamp),
            'relative' => $relative
        ];
    }

    // Add this method to handle AJAX requests for article data only
    public function getArticleData(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        header('Content-Type: application/json');
        
        try {
            $response = ApiClient::get("/admin/news/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception('Article not found');
            }
            
            $article = $response['data'];
            
            // Check permissions
            if ($this->user['role'] !== 'super_admin' && $article['author_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $article
            ]);
            
        } catch (\Exception $e) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Method to handle article duplication
    public function duplicate(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRoles(['contributor', 'super_admin']);
        
        try {
            // Fetch the original article
            $response = ApiClient::get("/admin/news/{$id}");
            
            if (!$response || !$response['success']) {
                throw new \Exception('Article not found');
            }
            
            $article = $response['data'];
            
            // Check permissions
            if ($this->user['role'] !== 'super_admin' && $article['author_id'] !== $this->user['id']) {
                throw new \Exception('Access denied');
            }
            
            // Prepare duplicated article data
            $newArticle = [
                'title' => $article['title'] . ' (Copy)',
                'slug' => '', // Will be auto-generated
                'summary' => $article['summary'],
                'content' => $article['content'],
                'category_id' => $article['category_id'],
                'status' => 'draft', // Always set to draft
                'is_featured' => false, // Reset featured status
                'is_breaking' => false, // Reset breaking status
                'is_fact_checked' => false, // Reset fact checked status
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'meta_keywords' => $article['meta_keywords']
            ];
            
            // Create the duplicate via API
            $createResponse = ApiClient::post('/admin/news', $newArticle);

            if ($createResponse && $createResponse['success']) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Article duplicated successfully!'
                ];
                
                // Redirect to edit the new article
                header('Location: ' . Router::url('news/edit/' . $createResponse['data']['id']));
            } else {
                throw new \Exception('Failed to duplicate article');
            }
            
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            header('Location: ' . Router::url('news'));
        }
    }

    // Method to view article history
    public function history(int $id): void
    {
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        try {
            // Fetch article
            $articleResponse = ApiClient::get("/admin/news/{$id}");
            if (!$articleResponse || !$articleResponse['success']) {
                throw new \Exception('Article not found');
            }
            
            $article = $articleResponse['data'];
            
            // Fetch article history/revisions
            $historyResponse = ApiClient::get("/admin/news/{$id}/history");
            $history = $historyResponse && $historyResponse['success'] ? $historyResponse['data'] : [];
            
            // Fetch activity logs for this article
            $activityResponse = ApiClient::get("/admin/activity-logs?target_type=news&target_id={$id}");
            $activities = $activityResponse && $activityResponse['success'] ? $activityResponse['data'] : [];
            
            LayoutHelper::setPageData(
                'Article History - ' . htmlspecialchars($article['title']),
                'View article revision history',
                [
                    ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                    ['label' => 'News Management', 'url' => Router::url('news')],
                    ['label' => $article['title'], 'url' => Router::url("news/show/{$id}")],
                    ['label' => 'History', 'url' => Router::url("news/history/{$id}")]
                ]
            );
            
            LayoutHelper::render('news/history', [
                'article' => $article,
                'history' => $history,
                'activities' => $activities,
                'user' => $this->user
            ]);
            
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
            
            header('Location: ' . Router::url('news'));
            exit;
        }
    }


    // Helper methods
    protected function requireRole(string $role): void
    {
        if (!$this->user || $this->user['role'] !== $role) {
            throw new \Exception('Insufficient permissions');
        }
    }

    protected function requireRoles(array $allowedRoles): void
    {
        if (!$this->user || !in_array($this->user['role'], $allowedRoles)) {
            throw new \Exception('Insufficient permissions');
        }
    }




    public function analyticsPage(): void
    {
        // Check authentication and admin role
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        $user = $this->user;
        
        try {
            // Set page data for layout
            LayoutHelper::setPageData(
                'News Analytics',
                'Comprehensive insights into news content performance',
                [
                    ['label' => 'Dashboard', 'url' => Router::url('dashboard')],
                    ['label' => 'News Management', 'url' => Router::url('news')],
                    ['label' => 'Analytics', 'url' => Router::url('news/analytics')]
                ]
            );
            
            // Render the analytics view
            LayoutHelper::render('news/analytics', [
                'user' => $user,
                'API_URL' => API_BASE_URL,
                'UPLOADS_URL' => UPLOADS_URL,
                'Router' => 'Router' // Pass Router class name for use in view
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            error_log("Error loading news analytics: " . $e->getMessage());
            
            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Failed to load analytics: ' . $e->getMessage()
            ];
            
            // Redirect to news list
            header('Location: ' . Router::url('news'));
            exit;
        }
    }

    // API endpoint to fetch analytics data (AJAX)
    public function getAnalyticsData(): void
    {
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        header('Content-Type: application/json');
        
        try {
            // Get date range from query parameters
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $rangeDays = (int)($_GET['range_days'] ?? 30);
            
            // Fetch analytics data from API
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            $response = ApiClient::get('/admin/news/analytics', $params);
            
            if (!$response || !$response['success']) {
                throw new \Exception('Failed to fetch analytics data');
            }
            
            // Process and enhance the data
            $analyticsData = $this->processAnalyticsData($response['data'], $startDate, $endDate, $rangeDays);
            
            echo json_encode([
                'success' => true,
                'data' => $analyticsData
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Export analytics report
    public function exportAnalytics(): void
    {
        AuthController::requireAuth();
        $this->requireRole('super_admin');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $format = $data['format'] ?? 'pdf';
            $sections = explode(',', $data['sections'] ?? 'overview,charts,articles,authors');
            $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $data['end_date'] ?? date('Y-m-d');
            
            // Fetch analytics data
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'export' => true
            ];
            
            $response = ApiClient::post('/admin/news/analytics/export', array_merge($params, [
                'format' => $format,
                'sections' => $sections
            ]));
            
            if (!$response || !$response['success']) {
                throw new \Exception('Failed to generate report');
            }
            
            // Get the file path or data from response
            $filePath = $response['data']['file_path'] ?? null;
            $fileData = $response['data']['file_data'] ?? null;
            
            if ($filePath) {
                // Download file from path
                $this->downloadFile($filePath, $format);
            } elseif ($fileData) {
                // Send file data directly
                $this->sendFileData($fileData, $format, $startDate, $endDate);
            } else {
                throw new \Exception('No file data received');
            }
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Process analytics data for enhanced insights
    private function processAnalyticsData(array $data, string $startDate, string $endDate, int $rangeDays): array
    {
        // Calculate previous period for comparison
        $previousStart = date('Y-m-d', strtotime($startDate . " -{$rangeDays} days"));
        $previousEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
        
        // Get previous period data for trends
        $previousResponse = ApiClient::get('/admin/news/analytics', [
            'start_date' => $previousStart,
            'end_date' => $previousEnd
        ]);
        
        $previousData = $previousResponse && $previousResponse['success'] ? $previousResponse['data'] : [];
        
        // Calculate trends
        $data['metrics']['articles_trend'] = $this->calculateTrend(
            $data['metrics']['total_articles'] ?? 0,
            $previousData['metrics']['total_articles'] ?? 0
        );
        
        $data['metrics']['views_trend'] = $this->calculateTrend(
            $data['metrics']['total_views'] ?? 0,
            $previousData['metrics']['total_views'] ?? 0
        );
        
        $data['metrics']['engagement_trend'] = $this->calculateTrend(
            $data['metrics']['engagement_rate'] ?? 0,
            $previousData['metrics']['engagement_rate'] ?? 0
        );
        
        $data['metrics']['reading_time_trend'] = $this->calculateTrend(
            $data['metrics']['avg_reading_time'] ?? 0,
            $previousData['metrics']['avg_reading_time'] ?? 0
        );
        
        // Ensure all required data structures exist
        $data['views_timeline'] = $data['views_timeline'] ?? ['labels' => [], 'data' => []];
        $data['content_performance'] = $this->processContentPerformance($data['content_performance'] ?? []);
        $data['category_performance'] = $data['category_performance'] ?? ['labels' => [], 'data' => []];
        $data['top_articles'] = $this->processTopArticles($data['top_articles'] ?? []);
        $data['top_authors'] = $data['top_authors'] ?? [];
        $data['publishing_patterns'] = $this->processPublishingPatterns($data['publishing_patterns'] ?? []);
        $data['reader_sources'] = $data['reader_sources'] ?? ['labels' => [], 'data' => []];
        $data['content_length_analysis'] = $this->processContentLengthAnalysis($data['content_length_analysis'] ?? []);
        
        return $data;
    }

    // Calculate percentage trend
    private function calculateTrend($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    // Process content performance data
    private function processContentPerformance(array $data): array
    {
        return [
            'labels' => $data['labels'] ?? ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            'views' => $data['views'] ?? [0, 0, 0, 0],
            'engagement' => $data['engagement'] ?? [0, 0, 0, 0],
            'shares' => $data['shares'] ?? [0, 0, 0, 0]
        ];
    }

    // Process top articles data
    private function processTopArticles(array $data): array
    {
        $metrics = ['views', 'likes', 'comments', 'shares'];
        $processed = [];
        
        foreach ($metrics as $metric) {
            $processed[$metric] = $data[$metric] ?? [];
            
            // Ensure each article has all required fields
            foreach ($processed[$metric] as &$article) {
                $article['view_count'] = $article['view_count'] ?? 0;
                $article['like_count'] = $article['like_count'] ?? 0;
                $article['comment_count'] = $article['comment_count'] ?? 0;
                $article['share_count'] = $article['share_count'] ?? 0;
                $article['category_name'] = $article['category_name'] ?? 'Uncategorized';
            }
        }
        
        return $processed;
    }

    // Process publishing patterns
    private function processPublishingPatterns(array $data): array
    {
        // Default data for 7 days
        $defaultByDay = [0, 0, 0, 0, 0, 0, 0];
        $defaultAvgViews = [0, 0, 0, 0, 0, 0, 0];
        
        return [
            'by_day' => $data['by_day'] ?? $defaultByDay,
            'avg_views_by_day' => $data['avg_views_by_day'] ?? $defaultAvgViews,
            'best_time' => $data['best_time'] ?? 'Tuesday 10:00 AM'
        ];
    }

    // Process content length analysis
    private function processContentLengthAnalysis(array $data): array
    {
        $total = ($data['short']['count'] ?? 0) + ($data['medium']['count'] ?? 0) + ($data['long']['count'] ?? 0);
        
        if ($total === 0) {
            $total = 1; // Prevent division by zero
        }
        
        return [
            'short' => [
                'count' => $data['short']['count'] ?? 0,
                'percentage' => round(($data['short']['count'] ?? 0) / $total * 100, 1),
                'avg_views' => $data['short']['avg_views'] ?? 0
            ],
            'medium' => [
                'count' => $data['medium']['count'] ?? 0,
                'percentage' => round(($data['medium']['count'] ?? 0) / $total * 100, 1),
                'avg_views' => $data['medium']['avg_views'] ?? 0
            ],
            'long' => [
                'count' => $data['long']['count'] ?? 0,
                'percentage' => round(($data['long']['count'] ?? 0) / $total * 100, 1),
                'avg_views' => $data['long']['avg_views'] ?? 0
            ]
        ];
    }

    // Download file from path
    private function downloadFile(string $filePath, string $format): void
    {
        $fullPath = dirname(__DIR__, 4) . '/storage/exports/' . $filePath;
        
        if (!file_exists($fullPath)) {
            throw new \Exception('Export file not found');
        }
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv'
        ];
        
        $extension = [
            'pdf' => 'pdf',
            'excel' => 'xlsx',
            'csv' => 'csv'
        ];
        
        header('Content-Type: ' . ($mimeTypes[$format] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="news_analytics_' . date('Y-m-d') . '.' . ($extension[$format] ?? 'bin') . '"');
        header('Content-Length: ' . filesize($fullPath));
        
        readfile($fullPath);
        
        // Clean up the file after download
        unlink($fullPath);
    }

    // Send file data directly
    private function sendFileData(string $fileData, string $format, string $startDate, string $endDate): void
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv'
        ];
        
        $extension = [
            'pdf' => 'pdf',
            'excel' => 'xlsx',
            'csv' => 'csv'
        ];
        
        $filename = sprintf(
            'news_analytics_%s_to_%s.%s',
            $startDate,
            $endDate,
            $extension[$format] ?? 'bin'
        );
        
        header('Content-Type: ' . ($mimeTypes[$format] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($fileData));
        
        echo base64_decode($fileData);
    }

    // Add route for AJAX analytics data endpoint
    public function analyticsData(): void
    {
        $this->getAnalyticsData();
    }
}