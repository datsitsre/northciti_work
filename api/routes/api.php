<?php

// api/routes/api.php - Updated API Routes with Media Moderation 

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use App\Core\Router;
use App\Controllers\Api\NewsController;
use App\Controllers\Api\EventController;
use App\Controllers\Api\CategoryController;
use App\Controllers\Api\TagController;
use App\Controllers\Api\CommentController;
use App\Controllers\Api\MediaController;
use App\Controllers\Api\UserController;
use App\Controllers\Api\SearchController;
use App\Controllers\Api\UploadController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

function registerApiRoutes(Router $router): void
{
    // Public endpoints
    $router->group('/northcity/api/public', function($router) {
        
        // News endpoints
        $router->get('/news', NewsController::class . '@index');
        $router->get('/news/featured', NewsController::class . '@featured');
        $router->get('/news/breaking', NewsController::class . '@breaking');
        $router->get('/news/trending', NewsController::class . '@trending');
        $router->get('/news/category/{slug}', NewsController::class . '@byCategory');
        $router->get('/news/tag/{slug}', NewsController::class . '@byTag');
        $router->get('/news/tag/{id}', NewsController::class . '@byTagId');
        $router->get('/news/{id}', NewsController::class . '@show');
        $router->post('/news/{id}/increase-view', NewsController::class . '@increaseView');
        
        // Events endpoints
        $router->get('/events', EventController::class . '@index');
        $router->get('/events/featured', EventController::class . '@featured');
        $router->get('/events/upcoming', EventController::class . '@upcoming');
        $router->get('/events/calendar', EventController::class . '@calendar');
        $router->get('/events/category/{slug}', EventController::class . '@byCategory');
        $router->get('/events/{id}', EventController::class . '@show');
        $router->post('/events/{id}/increase-view', EventController::class . '@increaseView');
        
        // Categories and tags
        $router->get('/categories', CategoryController::class . '@index');
        $router->get('/categories/{id}', CategoryController::class . '@show');
        $router->get('/categories/popular', CategoryController::class . '@popular');
        $router->get('/tags', TagController::class . '@index');
        $router->get('/tags/{slug}', TagController::class . '@show');
        
        // Comments (public read)
        $router->get('/{news}/{id}/comments', CommentController::class . '@getByContent');
        $router->get('/events/{id}/comments', CommentController::class . '@getByContent');
        
        // Search
        $router->get('/search', SearchController::class . '@search');
        $router->get('/search/suggestions', SearchController::class . '@suggestions');
        $router->get('/search/popular', SearchController::class . '@popular');
        
        // Public user profiles
        $router->get('/users/{id}/profile', UserController::class . '@publicProfile');
        
        // Public media (for approved content)
        $router->get('/media/{id}/download', MediaController::class . '@download');
        $router->get('/media/{id}/download-url', MediaController::class . '@getDownloadUrl');
        $router->get('/media/{id}/serve', MediaController::class . '@serveMedia');
    });

    // Authenticated endpoints
    $router->group('/northcity/api', function($router) {
        
        // User management
        $router->get('/users/me', UserController::class . '@profile');
        $router->put('/users/me', UserController::class . '@updateProfile');
        $router->post('/users/me/avatar', UserController::class . '@uploadAvatar');
        $router->get('/users/me/activity', UserController::class . '@activity');

        // Enhanced Bookmarks Management
        $router->get('/users/me/bookmarks', UserController::class . '@bookmarks');
        $router->get('/users/me/bookmarks/stats', UserController::class . '@bookmarkStats');
        $router->get('/users/me/bookmarks/categories', UserController::class . '@bookmarksByCategory');

        // Enhanced bookmark operations
        $router->post('/bookmarks/organize', UserController::class . '@organizeBookmarks');
        $router->put('/bookmarks/{id}/category', UserController::class . '@updateBookmarkCategory');
        $router->post('/bookmarks/bulk-action', UserController::class . '@bulkBookmarkAction');
        
        // Content interactions
        $router->get('/news', NewsController::class . '@index');
        $router->get('/news/featured', NewsController::class . '@featured');
        $router->get('/news/breaking', NewsController::class . '@breaking');
        $router->get('/news/trending', NewsController::class . '@trending');
        $router->post('/news/{id}/like', NewsController::class . '@like');
        $router->delete('/news/{id}/like', NewsController::class . '@like');
        $router->get('/news/{id}/like-status', NewsController::class . '@likeStatus');
        $router->get('/news/{id}/bookmark-status', NewsController::class . '@bookmarkStatus');
        $router->post('/news/{id}/bookmark', NewsController::class . '@bookmark');
        $router->delete('/news/{id}/bookmark', NewsController::class . '@bookmark');
        $router->get('/news/category/{slug}', NewsController::class . '@byCategory');
        $router->get('/news/tag/{slug}', NewsController::class . '@byTag');
        $router->get('/news/tag/{id}', NewsController::class . '@byTagId');
        $router->get('/news/{id}', NewsController::class . '@show');
        $router->post('/news/{id}/increase-view', NewsController::class . '@increaseView');

        // Events endpoints
        $router->get('/events', EventController::class . '@index');
        $router->get('/events/featured', EventController::class . '@featured');
        $router->get('/events/upcoming', EventController::class . '@upcoming');
        $router->get('/events/statistics', EventController::class . '@statistics'); 
        $router->get('/events/calendar', EventController::class . '@calendar'); 
        $router->get('/events/{id}/attendees', EventController::class . '@attendees');  
        $router->get('/events/category/{slug}', EventController::class . '@byCategory');
        $router->get('/events/{id}', EventController::class . '@show');
        $router->get('/events/{id}/like-status', EventController::class . '@likeStatus');
        $router->get('/events/{id}/bookmark-status', EventController::class . '@bookmarkStatus');
        $router->get('/events/{id}/attendance-status', EventController::class . '@attendanceStatus');
        $router->post('/events/{id}/increase-view', EventController::class . '@increaseView');
        $router->post('/events/{id}/like', EventController::class . '@like');
        $router->post('/events/{id}/attend', EventController::class . '@attend');
        $router->post('/events/{id}/bookmark', EventController::class . '@bookmark');
        $router->delete('/events/{id}/attend', EventController::class . '@attend');
        $router->delete('/events/{id}/like', EventController::class . '@like');
        $router->delete('/events/{id}/bookmark', EventController::class . '@bookmark');
        
        // Comments
        $router->get('/{news}/{id}/comments', CommentController::class . '@getByContent');
        $router->get('/events/{id}/comments', CommentController::class . '@getByContent');
        $router->get('/comments/me', CommentController::class . '@getUserComments');
        $router->get('/comments/{id}/replies', CommentController::class . '@getReplies');
        $router->get('/comments/trending', CommentController::class . '@getTrending');
        $router->post('/{news}/{id}/comments', CommentController::class . '@create');
        $router->post('/{events}/{id}/comments', CommentController::class . '@create');
        $router->put('/comments/{id}', CommentController::class . '@update');
        $router->delete('/comments/{id}', CommentController::class . '@delete');
        $router->post('/comments/{id}/vote', CommentController::class . '@vote');
        $router->post('/comments/{id}/flag', CommentController::class . '@flag');
        // Comment voting and interaction
        $router->post('/comments/{id}/vote', CommentController::class . '@vote');
        $router->delete('/comments/{id}/vote', CommentController::class . '@removeVote');
        $router->post('/comments/{id}/flag', CommentController::class . '@flag');
        // Comment statistics for content
        $router->get('/news/{id}/comments/stats', CommentController::class . '@getCommentStats');
        $router->get('/events/{id}/comments/stats', CommentController::class . '@getCommentStats');
        
        // Media uploads and management
        $router->post('/media/upload', MediaController::class . '@upload');
        $router->get('/media/my-uploads', MediaController::class . '@myUploads');
        $router->get('/media/{id}', MediaController::class . '@show');
        $router->put('/media/{id}', MediaController::class . '@update');
        $router->delete('/media/{id}', MediaController::class . '@delete');
        $router->post('/media/{id}/flag', MediaController::class . '@flagMedia'); // User can flag media
        $router->get('/media/{id}/download', MediaController::class . '@download');
        $router->get('/media/{id}/download-url', MediaController::class . '@getDownloadUrl');
        $router->get('/media/{id}/serve', MediaController::class . '@serveMedia');

        // Categories and tags
        $router->get('/categories', CategoryController::class . '@index');
        $router->get('/categories/{id}', CategoryController::class . '@show');
        $router->get('/categories/popular', CategoryController::class . '@popular');
        $router->get('/tags', TagController::class . '@index');
        $router->get('/tags/{slug}', TagController::class . '@show');
        
        // Subscriptions
        $router->post('/subscriptions', UserController::class . '@subscribe');
        $router->put('/subscriptions/{id}', UserController::class . '@updateSubscription');
        $router->delete('/subscriptions/{id}', UserController::class . '@unsubscribe');

        // Search
        $router->get('/search', SearchController::class . '@search');
        $router->get('/search/suggestions', SearchController::class . '@suggestions');
        $router->get('/search/popular', SearchController::class . '@popular');

        // Public user profiles
        $router->get('/users/{id}/profile', UserController::class . '@publicProfile');
        
    }, [AuthMiddleware::class]);

    // Contributor endpoints
    $router->group('/northcity/api/contributor', function($router) {
        
        // Content management
        $router->get('/news', NewsController::class . '@myNews');
        $router->post('/news/create', NewsController::class . '@create');
        $router->post('/news/{id}/update', NewsController::class . '@update');
        $router->get('/news/{id}', NewsController::class . '@adminShow');
        $router->put('/news/{id}', NewsController::class . '@update');
        $router->delete('/news/{id}/delete', NewsController::class . '@delete');
        $router->post('/news/{id}/publish', NewsController::class . '@requestPublish');
        $router->get('/news/moderation', NewsController::class . '@moderation');
        $router->get('/news/analytics', NewsController::class . '@analytics');
        $router->get('/news/statistics', NewsController::class . '@statistics');
        $router->get('/news/deep-analytics', NewsController::class . '@getAnalytics');
        $router->get('/news/{id}/tag', NewsController::class . '@byTagId');
        
        $router->get('/events', EventController::class . '@myEvents');
        $router->post('/events', EventController::class . '@create');
        $router->put('/events/{id}', EventController::class . '@update');
        $router->delete('/events/{id}', EventController::class . '@delete');
        $router->post('/events/{id}/publish', EventController::class . '@requestPublish');
        
        // Analytics for own content
        $router->get('/analytics/overview', UserController::class . '@analyticsOverview');
        $router->get('/analytics/news/{id}', NewsController::class . '@analytics');
        $router->get('/analytics/events/{id}', EventController::class . '@analytics');
        
        // Media management for contributors
        $router->get('/media', MediaController::class . '@index');
        $router->post('/media/upload', MediaController::class . '@upload');
        $router->put('/media/{id}', MediaController::class . '@update');
        $router->delete('/media/{id}', MediaController::class . '@delete');
        $router->get('/media/statistics', MediaController::class . '@myStatistics');
        
        $router->get('/media/{id}/download', MediaController::class . '@download');
        $router->get('/media/{id}/download-url', MediaController::class . '@getDownloadUrl');
        $router->get('/media/{id}/serve', MediaController::class . '@serveMedia');

        // Upload routes
        $router->post('/upload/image', UploadController::class . '@uploadImage');
        $router->delete('/upload/delete', UploadController::class . '@deleteImage');
        $router->post('/upload/cleanup', UploadController::class . '@cleanupOrphanedImages');
        
    }, [AuthMiddleware::class, RoleMiddleware::requireRole('contributor', 'super_admin')]);
}