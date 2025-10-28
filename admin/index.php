<?php

// admin/index.php - Updated Entry Point with Enhanced Routing

// Define admin access constant
define('ADMIN_ACCESS', true);

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Include configuration files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/router.php';

// Include helper functions
require_once __DIR__ . '/src/Helpers/functions.php';
require_once __DIR__ . '/src/Helpers/ApiClient.php';

// Include models and controllers
require_once __DIR__ . '/src/Models/User.php';
require_once __DIR__ . '/src/Models/Media.php';
require_once __DIR__ . '/src/Controllers/AuthController.php';
require_once __DIR__ . '/src/Controllers/DashboardController.php';
require_once __DIR__ . '/src/Controllers/UserController.php';
require_once __DIR__ . '/src/Controllers/ProfileController.php';
require_once __DIR__ . '/src/Controllers/SettingsController.php';
require_once __DIR__ . '/src/Controllers/ActivitiesController.php';
require_once __DIR__ . '/src/Controllers/MediaController.php';
require_once __DIR__ . '/src/Controllers/ModerationController.php';
require_once __DIR__ . '/src/Controllers/NewsController.php';
require_once __DIR__ . '/src/Controllers/NewsManagementController.php';
require_once __DIR__ . '/src/Controllers/EventsController.php';
require_once __DIR__ . '/src/Controllers/CategoriesController.php';
require_once __DIR__ . '/src/Controllers/CommentsController.php';


// Create router instance
$router = new Router('/northcity/admin');

// Define all routes
$router->get('/', function() {
    // Check if user is logged in
    if (AuthController::isLoggedIn()) {
        redirect(Router::url('dashboard'));
    } else {
        redirect(Router::url('login'));
    }
});

// Authentication routes
$router->get('/login', function() {
    AuthController::showLogin();
});

$router->post('/login', function() {
    AuthController::handleLogin();
});

$router->get('/logout', function() {
    AuthController::logout();
});

// Dashboard routes (require authentication)
$router->get('/dashboard', function() {
    AuthController::requireAuth();
    DashboardController::index();
});

// API routes for AJAX calls
$router->get('/api/dashboard-stats', function() {
    AuthController::requireAuth();
    header('Content-Type: application/json');
    
    $stats = DashboardController::getDashboardStats();
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
});

// User Management Routes
$router->get('/users', function() {
    UserController::index();
});

$router->get('/users/create', function() {
    UserController::create();
});

$router->post('/users/store', function() {
    UserController::store();
});

$router->get('/users/{id}', function($id) {
    UserController::show($id);
});

$router->get('/users/{id}/edit', function($id) {
    UserController::edit($id);
});

$router->post('/users/{id}/update', function($id) {
    UserController::update($id);
});

$router->post('/users/{id}/delete', function($id) {
    UserController::delete($id);
});

$router->post('/users/{id}/status', function($id) {
    UserController::updateStatus($id);
});

$router->post('/users/{id}/role', function($id) {
    UserController::updateRole($id);
});

$router->post('/users/bulk-action', function() {
    UserController::bulkAction();
});

// Profile routes
$router->get('/profile', function() {
    ProfileController::index();
});

$router->post('/profile/update', function() {
    ProfileController::update();
});

// Activities routes
$router->get('/activities', function() {
    ActivitiesController::index();
});

$router->get('/activities/show', function() {
    ActivitiesController::show();
});

$router->get('/activities/export', function() {
    ActivitiesController::export();
});

$router->post('/activities/cleanup', function() {
    ActivitiesController::cleanup();
});

// Media Management Routes
// Media Management Routes - Updated to use controller instances
$router->get('/media', function() {
    $controller = new MediaController();
    $controller->index();
});

$router->get('/media/show', function() {
    $controller = new MediaController();
    $controller->show();
});

$router->get('/media/upload', function() {
    $controller = new MediaController();
    $controller->upload();
});

$router->post('/media/upload', function() {
    $controller = new MediaController();
    $controller->upload();
});

$router->get('/media/edit', function() {
    $controller = new MediaController();
    $controller->edit();
});

$router->post('/media/edit', function() {
    $controller = new MediaController();
    $controller->edit();
});

$router->post('/media/moderate', function() {
    $controller = new MediaController();
    $controller->moderate();
});

$router->post('/media/bulk-action', function() {
    $controller = new MediaController();
    $controller->bulkAction();
});

$router->post('/media/delete', function() {
    $controller = new MediaController();
    $controller->delete();
});

$router->get('/media/download', function() {
    $controller = new MediaController();
    $controller->download();
});
$router->get('/media/{id}/download', function() {
    $controller = new MediaController();
    $controller->download();
});

$router->post('/media/cleanup', function() {
    $controller = new MediaController();
    $controller->cleanup();
});

$router->get('/media/stats', function() {
    $controller = new MediaController();
    $controller->stats();
});

// Moderation Routes
$router->get('/moderation', function() {
    $controller = new ModerationController();
    $controller->index();
});

$router->get('/moderation/show', function() {
    $controller = new ModerationController();
    $controller->show();
});

$router->post('/moderation/moderate', function() {
    $controller = new ModerationController();
    $controller->moderate();
});

$router->post('/moderation/bulk-moderate', function() {
    $controller = new ModerationController();
    $controller->bulkModerate();
});

$router->get('/moderation/statistics', function() {
    $controller = new ModerationController();
    $controller->statistics();
});

$router->get('/moderation/export', function() {
    $controller = new ModerationController();
    $controller->exportReport();
});

// API Routes for Moderation (add to your API router)
$router->get('/api/admin/moderation/queue', function() {
    $controller = new \App\Controllers\Admin\MediaModerationController();
    $controller->index();
});

$router->get('/api/admin/moderation/media/{id}', function($id) {
    $controller = new \App\Controllers\Admin\MediaModerationController();
    $controller->show($id);
});

$router->get('/api/admin/moderation/statistics', function() {
    $controller = new \App\Controllers\Admin\MediaModerationController();
    $controller->statistics();
});

$router->post('/api/admin/moderation/bulk', function() {
    $controller = new \App\Controllers\Admin\MediaModerationController();
    $controller->bulkModerate();
});

$router->get('/api/admin/moderation/export', function() {
    $controller = new \App\Controllers\Admin\MediaModerationController();
    $controller->exportReport();
});

// API Routes (add to API router)
$router->get('/api/media/{id}/download', function($id) {
    $controller = new \App\Controllers\Api\MediaController();
    $controller->download(new Request(), $id);
});

// Alternative API route for direct file access
$router->get('/api/files/{id}', function($id) {
    $controller = new \App\Controllers\Api\MediaController();
    $controller->download(new Request(), $id);
});

// News routes
$router->get('/news', function() {
    $controller = new NewsController();
    $controller->index();
});

$router->get('/news/show/{id}', function($id) {
    $controller = new NewsController();
    $controller->show((int)$id);
});

$router->get('/news/duplicate/{id}', function($id) {
    $controller = new NewsController();
    $controller->duplicate((int)$id);
});

$router->get('/news/history/{id}', function($id) {
    $controller = new NewsController();
    $controller->history((int)$id);
});

$router->get('/news/edit/{id}', function($id) {
    $controller = new NewsController();
    $controller->edit((int)$id);
});

$router->get('/news/analytics', function() {
    $controller = new NewsController();
    $controller->analyticsPage();
});


// Events management routes
$router->get('/events', function() {
    $controller = new EventsController();
    $controller->index();
});

// Events pages
$router->get('/events/create', function() {
    $controller = new EventsController();
    $controller->create();
});

$router->get('/events/edit/{id}', function($id) {
    $controller = new EventsController();
    $controller->edit((int)$id);
});

$router->get('/events/show/{id}', function($id) {
    $controller = new EventsController();
    $controller->show((int)$id);
});

$router->get('/events/moderation', function() {
    $controller = new EventsController();
    $controller->moderation();
});

$router->get('/events/analytics', function() {
    $controller = new EventsController();
    $controller->analyticsPage();
});

$router->get('/events/calendar', function() {
    $controller = new EventsController();
    $controller->calendar();
});

$router->get('/events/{id}/attendees', function($id) {
    $controller = new EventsController();
    $controller->attendees((int)$id);
});

// Actions
$router->post('/events/{id}/duplicate', function($id) {
    $controller = new EventsController();
    $controller->duplicate((int)$id);
});

$router->get('/events/{id}/attendees/export', function($id) {
    $controller = new EventsController();
    $controller->exportAttendees((int)$id);
});


$router->get('/categories', function() {
    $controller = new CategoriesController();
    $controller->index();
});


$router->get('/comments', function() {
    $controller = new CommentsController();
    $controller->index();
});


// Settings routes
$router->get('/settings/general', function() {
    SettingsController::general();
});

$router->post('/settings/general/update', function() {
    SettingsController::updateGeneral();
});

$router->get('/settings/email', function() {
    SettingsController::email();
});

$router->post('/settings/email/update', function() {
    SettingsController::updateEmail();
});

$router->post('/settings/email/test', function() {
    SettingsController::testEmail();
});

$router->get('/settings/api', function() {
    SettingsController::api();
});

$router->post('/settings/api/update', function() {
    SettingsController::updateApi();
});

// Redirect /settings to /settings/general
$router->get('/settings', function() {
    redirect(Router::url('settings/general'));
});

// Handle legacy index.php requests
$router->get('/index', function() {
    redirect(Router::url());
});

$router->get('/index.php', function() {
    redirect(Router::url());
});

// Handle the request
try {
    $router->dispatch();
} catch (Exception $e) {
    // Log the error
    error_log("Admin Panel Error: " . $e->getMessage());
    
    if (APP_DEBUG) {
        echo "<div style='padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; margin: 20px;'>";
        echo "<h1 style='color: #dc3545;'>Admin Panel Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
        echo "</div>";
    } else {
        http_response_code(500);
        include __DIR__ . '/templates/errors/500.php';
    }
}