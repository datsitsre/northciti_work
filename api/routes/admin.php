<?php
// api/routes/admin.php - Cleaned Admin Routes for Comment Moderation

use App\Core\Router;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\UserManagementController;
use App\Controllers\Admin\ContentModerationController;
use App\Controllers\Admin\CommentModerationController;  // New unified controller
use App\Controllers\Admin\MediaModerationController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\AnalyticsController;
use App\Controllers\Api\NewsController;
use App\Controllers\Api\EventController;
use App\Controllers\Api\CommentController;
use App\Controllers\Api\CategoryController;
use App\Controllers\Api\TagController;
use App\Controllers\Api\MediaController;
use App\Controllers\Api\UploadController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

function registerAdminRoutes(Router $router): void
{
    $router->group('/northcity/api/admin', function($router) {
        
        // Dashboard
        $router->get('/dashboard', DashboardController::class . '@index');
        $router->get('/dashboard/stats', DashboardController::class . '@stats');
        $router->get('/dashboard/recent-activity', DashboardController::class . '@recentActivity');

        // UNIFIED COMMENT MODERATION - Replace all previous comment routes with these
        $router->get('/comments/moderation/queue', CommentModerationController::class . '@queue');
        $router->get('/comments/moderation/statistics', CommentModerationController::class . '@statistics');
        $router->get('/comments/moderation/flagged', CommentModerationController::class . '@flaggedComments');
        $router->post('/comments/moderation/auto-moderate', CommentModerationController::class . '@autoModerate');
        
        // Individual comment moderation
        $router->get('/comments/{id}', CommentModerationController::class . '@show');
        $router->put('/comments/{id}/moderate', CommentModerationController::class . '@moderate');
        $router->post('/comments/moderate/bulk', CommentModerationController::class . '@bulkModerate');
        $router->delete('/comments/{id}', CommentController::class . '@delete');
        
        // Comment flags management
        $router->get('/comments/flags', CommentModerationController::class . '@getFlags');
        $router->put('/comments/flags/{id}', CommentModerationController::class . '@handleFlag');

        // REMOVE ALL THESE REDUNDANT ROUTES - they're causing conflicts:
        // $router->get('/moderation/queue', ContentModerationController::class . '@queue');
        // $router->get('/moderation/comments', ContentModerationController::class . '@comments');
        // $router->put('/moderation/comments/{id}', ContentModerationController::class . '@moderateComment');
        
        // Keep only general content moderation (news, events, media - NOT comments)
        $router->get('/moderation/queue', ContentModerationController::class . '@queue');
        $router->get('/moderation/flags', ContentModerationController::class . '@flags');
        $router->get('/moderation/statistics', ContentModerationController::class . '@statistics');
        $router->put('/moderation/content/{type}/{id}', ContentModerationController::class . '@moderate');
        $router->put('/moderation/flags/{id}', ContentModerationController::class . '@handleFlag');
        $router->post('/moderation/bulk', ContentModerationController::class . '@bulkModerate');

        // User Management
        $router->get('/users', UserManagementController::class . '@index');
        $router->get('/users/{id}', UserManagementController::class . '@show');
        $router->put('/users/{id}', UserManagementController::class . '@update');
        $router->put('/users/{id}/status', UserManagementController::class . '@updateStatus');
        $router->put('/users/{id}/role', UserManagementController::class . '@updateRole');
        $router->delete('/users/{id}', UserManagementController::class . '@delete');
        $router->post('/users/{id}/send-email', UserManagementController::class . '@sendEmail');
        $router->get('/users/statistics', UserManagementController::class . '@statistics');
        $router->get('/users/export', UserManagementController::class . '@export');

        // Media Management
        $router->get('/media', MediaController::class . '@adminIndex');
        $router->get('/media/statistics', MediaController::class . '@statistics');
        $router->get('/media/{id}', MediaController::class . '@show');
        $router->put('/media/{id}', MediaController::class . '@update');
        $router->delete('/media/{id}', MediaController::class . '@delete');
        $router->delete('/media/{id}/force', MediaController::class . '@forceDelete');
        $router->post('/media/cleanup', MediaController::class . '@cleanup');
        $router->post('/media/upload', MediaController::class . '@upload');
        
        // Media Moderation
        $router->get('/media/moderation', MediaModerationController::class . '@index');
        $router->get('/media/moderation/statistics', MediaModerationController::class . '@statistics');
        $router->get('/media/moderation/export', MediaModerationController::class . '@exportReport');
        $router->post('/media/moderation/bulk', MediaModerationController::class . '@bulkModerate');
        $router->get('/media/moderation/{id}', MediaModerationController::class . '@show');
        $router->put('/media/moderation/{id}', MediaModerationController::class . '@moderate');
        $router->post('/media/{id}/flag', MediaModerationController::class . '@flagMedia');
        
        // Content Management
        $router->get('/news', NewsController::class . '@adminIndex');
        $router->post('/news', NewsController::class . '@create');
        $router->get('/news/deep-analytics', NewsController::class . '@getAnalytics');
        $router->get('/news/statistics', NewsController::class . '@statistics');
        $router->get('/news/analytics', NewsController::class . '@getAnalytics');
        $router->get('/news/{id}', NewsController::class . '@adminShow');
        $router->put('/news/{id}', NewsController::class . '@update');
        $router->post('/news/{id}/update', NewsController::class . '@update'); // for form update multipart
        $router->put('/news/{id}/status', NewsController::class . '@updateStatus');
        $router->delete('/news/{id}', NewsController::class . '@delete');
        
        // Events Management
        $router->get('/events', EventController::class . '@adminIndex');
        $router->get('/events/statistics', EventController::class . '@statistics');
        $router->get('/events/analytics', EventController::class . '@getAnalytics');
        $router->get('/events/moderation', EventController::class . '@moderation');
        $router->get('/events/calendar', EventController::class . '@calendar');
        $router->get('/events/{id}', EventController::class . '@adminShow');
        $router->post('/events', EventController::class . '@create');
        $router->put('/events/{id}', EventController::class . '@update');
        $router->put('/events/{id}/status', EventController::class . '@updateStatus');
        $router->delete('/events/{id}', EventController::class . '@delete');
        $router->delete('/events/{id}/force', EventController::class . '@forceDelete');
        // Event attendees management
        $router->get('/events/{id}/attendees', EventController::class . '@attendees');
        $router->get('/events/{id}/attendees/export', EventController::class . '@exportAttendees');
        // Additional admin-only event methods that might be needed
        $router->post('/events/{id}/feature', EventController::class . '@toggleFeature');
        $router->post('/events/{id}/cancel', EventController::class . '@cancelEvent');
        $router->get('/events/{id}/analytics', EventController::class . '@analytics');
        // Bulk operations
        $router->post('/events/bulk/status', EventController::class . '@bulkUpdateStatus');
        $router->post('/events/bulk/delete', EventController::class . '@bulkDelete');
        
        // Category & Tag Management
        $router->get('/categories', CategoryController::class . '@index');
        $router->post('/categories', CategoryController::class . '@create');
        $router->put('/categories/{id}', CategoryController::class . '@update');
        $router->delete('/categories/{id}', CategoryController::class . '@delete');
        $router->get('/categories/statistics', CategoryController::class . '@statistics');
        
        $router->get('/tags', TagController::class . '@index');
        $router->post('/tags', TagController::class . '@create');
        $router->put('/tags/{id}', TagController::class . '@update');
        $router->delete('/tags/{id}', TagController::class . '@delete');
        
        // Analytics
        $router->get('/analytics/overview', AnalyticsController::class . '@overview');
        $router->get('/analytics/content', AnalyticsController::class . '@content');
        $router->get('/analytics/users', AnalyticsController::class . '@users');
        $router->get('/analytics/traffic', AnalyticsController::class . '@traffic');
        
        // System Settings
        $router->get('/settings', SettingsController::class . '@index');
        $router->put('/settings', SettingsController::class . '@update');
        $router->get('/settings/email/test', SettingsController::class . '@testEmail');
        $router->post('/settings/cache/clear', SettingsController::class . '@clearCache');
        
        // Upload routes
        $router->post('/upload/image', UploadController::class . '@uploadImage');
        $router->delete('/upload/delete', UploadController::class . '@deleteImage');

        // Download endpoints for exports
        $router->get('/downloads/{filename}', function($request, $filename) {
            $filepath = dirname(__DIR__, 3) . '/storage/exports/' . $filename;
            
            if (!file_exists($filepath) || !is_file($filepath)) {
                throw new \App\Exceptions\NotFoundException('File not found');
            }
            
            $response = new \App\Core\Response();
            return $response->download($filepath, $filename);
        });
        
    }, [AuthMiddleware::class, RoleMiddleware::requireRole('super_admin')]);
}