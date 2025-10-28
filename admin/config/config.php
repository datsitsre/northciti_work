<?php

// admin/config/config.php - Main Configuration File

// Prevent direct access
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted');
}

// Environment Configuration
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_NAME', $_ENV['APP_NAME'] ?? 'NorthCity News Platform');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://10.30.252.49/northcity');
define('APP_DEBUG', $_ENV['APP_DEBUG'] === 'true');
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'UTC');

define('STORAGE_URL', APP_URL . '/storage');
define('UPLOADS_URL', STORAGE_URL . '/uploads');

define('ADMIN_APP_URL', $_ENV['ADMIN_APP_URL'] ?? 'http://10.30.252.49/northcity/admin');
// JS config file route
define("CONFIG_JS_FILE_PATH", ADMIN_APP_URL . '/config/config.js');

// API Configuration
define('API_BASE_URL', APP_URL . '/api');
define('API_TIMEOUT', 30);

// Session Configuration
define('SESSION_NAME', 'admin_session');
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_NAME', 'csrf_token');

// Security Configuration
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5 minutes
define('CSRF_TOKEN_EXPIRE', (int)($_ENV['CSRF_TOKEN_EXPIRE'] ?? 3600));
define('PASSWORD_MIN_LENGTH', (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 8));

// Logging Configuration
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'error');
define('LOG_PATH', $_ENV['LOG_PATH'] ?? __DIR__ . '/../logs');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'tiff', 'webp', 'avif', 'jpeg', 'png', 'gif']);

// Pagination Configuration
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting based on debug mode
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../storage/logs/admin.log');
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']), // Use secure cookies if HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Configure session security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    
    session_name(SESSION_NAME);
    session_start();
}

// CSRF Protection
// CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Utility Functions
function redirect(string $url, int $statusCode = 302): void
{
    header("Location: $url", true, $statusCode);
    exit;
}

// Helper function for base URL
function baseUrl($path = '') {
    return APP_URL . '/admin' . ($path ? '/' . ltrim($path, '/') : '');
}

function getCurrentUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return "$protocol://$host$uri";
}

function sanitizeInput(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}


// Set custom error handler for production
if (!APP_DEBUG) {
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $logMessage = sprintf(
            "[%s] %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $message,
            $file,
            $line
        );
        
        error_log($logMessage, 3, LOG_PATH . '/admin_errors.log');
        
        return true;
    });
}

// Helper function to check if we're in maintenance mode
function isMaintenanceMode(): bool {
    return file_exists(__DIR__ . '/../.maintenance');
}


// Helper function to get config value with default
function config(string $key, $default = null) {
    $configs = [
        'app.env' => APP_ENV,
        'app.debug' => APP_DEBUG,
        'app.url' => APP_URL,
        'api.base_url' => API_BASE_URL,
        'api.timeout' => API_TIMEOUT,
        'admin.session_timeout' => ADMIN_SESSION_TIMEOUT,
        'admin.remember_duration' => ADMIN_REMEMBER_DURATION,
        'upload.max_size' => MAX_UPLOAD_SIZE,
        'upload.allowed_types' => ALLOWED_FILE_TYPES,
        'security.csrf_expire' => CSRF_TOKEN_EXPIRE,
        'security.password_min_length' => PASSWORD_MIN_LENGTH,
        'pagination.default_size' => DEFAULT_PAGE_SIZE,
        'pagination.max_size' => MAX_PAGE_SIZE,
        'log.level' => LOG_LEVEL,
        'log.path' => LOG_PATH,
        'app.timezone' => DEFAULT_TIMEZONE
    ];
    
    return $configs[$key] ?? $default;
}

// Check for maintenance mode
if (isMaintenanceMode() && !APP_DEBUG) {
    include __DIR__ . '/../templates/maintenance.php';
    exit;
}