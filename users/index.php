<?php

// users/index.php - Updated Frontend Router for Clean URLs

// Define admin access constant
define('USERS_ACCESS', true);

// Error reporting - adjust for production
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/storage/logs/php_errors.log');

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

// ================================
// LOAD DEPENDENCIES
// ================================
// Include configuration files
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/router.php';
// Include services files
require_once __DIR__ . '/services/security.php';
require_once __DIR__ . '/services/validators.php';
require_once __DIR__ . '/services/functions.php';
// Include controllers
require_once __DIR__ . '/controllers/AuthController.php';

// ================================
// SECURITY INITIALIZATION
// ================================
// Validate CSRF for POST requests
FrontendSecurity::validateCSRF();

// Sanitize all input
$_GET = FrontendSecurity::sanitizeInput($_GET);
$_POST = FrontendSecurity::sanitizeInput($_POST);

// ================================
// UPDATED URL PARSING FOR CLEAN URLS
// ================================

// Get the frontend path from the main router
$frontend_path = $_SERVER['FRONTEND_PATH'] ?? '';
$frontend_path = trim($frontend_path, '/');

// Parse path segments from the clean frontend path
$path_segments = !empty($frontend_path) ? explode('/', $frontend_path) : [];

// Validate route
if (!FrontendSecurity::validateRoute($path_segments)) {
    Router::render404();
    exit;
}

// Route the request
initializeRememberMe();
routeRequest($path_segments);


/**
 * Add this to your bootstrap file or main entry point
 */
function initializeRememberMe() {
    // Only check remember me on public pages
    if (!FrontendSecurity::isAuthenticated()) {
        $authController = new AuthController();
        $authController->checkRememberMe();
    }
}

/**
 * Maintenance task (run via cron job)
 * Run daily at 2 AM
 * 0 2 * * * /path/to/php /path/to/cleanup-script.php
 */
function cleanupExpiredTokens() {
    $userModel = new User();
    $deleted = $userModel->cleanExpiredRememberTokens();
    error_log("Cleaned up {$deleted} expired remember tokens");
}

// ================================
// MAIN ROUTING FUNCTION - UPDATED
// ================================
function routeRequest($segments) {
    $page = $segments[0] ?? 'home';
    $action = $segments[1] ?? null;
    $id = $segments[2] ?? null;
    $subId = $segments[3] ?? null;
    
    // Validate segments
    if ($action && !InputValidator::validateAlphaNumeric($action)) {
        Router::render404();
        return;
    }
    
    if ($id && !InputValidator::validateId($id) && !InputValidator::validateSlug($id)) {
        Router::render404();
        return;
    }
    
    switch ($page) {
        case '':
        case 'home':
            Router::renderPage('home');
            break;
            
        case 'news':
            Router::handleNewsRoutes($action, $id);
            break;
            
        case 'events':
            Router::handleEventRoutes($action, $id);
            break;
            
        case 'user':
            switch ($action) {
                case 'bookmarks':
                    // Handle bookmark-specific routes
                    Router::handleBookmarksRoute($id, $subId);
                    break;
                    
                default:
                    // Handle other user routes
                    Router::handleUserRoutes($action, $id);
                    break;
            }
            break;
            
        case 'auth':
            Router::handleAuthRoutes($action);
            break;
            
        case 'search':
            Router::handleSearchRoute();
            break;
            
        case 'about':
            Router::renderPage('about');
            break;
            
        case 'contact':
            Router::renderPage('contact');
            break;
            
        // Handle direct user pages (for clean URLs)
        case 'bookmarks':
            // Redirect to user/bookmarks for consistency
            if (FrontendSecurity::isAuthenticated()) {
                Router::handleBookmarksRoute($action, $id);
            } else {
                FrontendSecurity::redirectToLogin();
            }
            break;
            
        case 'profile':
            // Redirect to user/profile for consistency
            if (FrontendSecurity::isAuthenticated()) {
                Router::handleUserRoutes('profile', $action);
            } else {
                FrontendSecurity::redirectToLogin();
            }
            break;
            
        case 'dashboard':
            // Redirect to user/dashboard for consistency
            if (FrontendSecurity::isAuthenticated()) {
                Router::handleUserRoutes('dashboard', null);
            } else {
                FrontendSecurity::redirectToLogin();
            }
            break;
            
        case 'settings':
            // Redirect to user/settings for consistency
            if (FrontendSecurity::isAuthenticated()) {
                Router::handleUserRoutes('settings', null);
            } else {
                FrontendSecurity::redirectToLogin();
            }
            break;
            
        default:
            Router::render404();
            break;
    }
}

?>