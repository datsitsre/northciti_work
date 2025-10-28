<?php
// Secure Root index.php - Updated for Clean URLs

// ================================
// SECURITY INITIALIZATION
// ================================

// Disable PHP information disclosure
ini_set('expose_php', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/storage/logs/php_errors.log');

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.name', 'PHEME_SESSION');
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        session_start();
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    } else {
        session_start();
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ================================
// SECURITY HEADERS
// ================================

// Prevent clickjacking
header('X-Frame-Options: DENY');
header('Access-Control-Allow-Origin: *');

// XSS Protection
header('X-XSS-Protection: 1; mode=block');

// Content Type Sniffing Protection
header('X-Content-Type-Options: nosniff');

// Referrer Policy
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
$csp = "default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
       "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
       "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
       "img-src 'self' data: https:; " .
       "media-src 'self'; " .
       "object-src 'none'; " .
       "base-uri 'self'; " .
       "form-action 'self'; " .
       "frame-ancestors 'none';";
header("Content-Security-Policy: $csp");

// Strict Transport Security (HTTPS only)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Permissions Policy (formerly Feature Policy)
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

// ================================
// SECURITY CLASSES (keeping your existing SecurityManager class)
// ================================

class SecurityManager {
    private static $rateLimitFile = __DIR__ . '/storage/security/rate_limits.json';
    private static $blockedIpsFile = __DIR__ . '/storage/security/blocked_ips.json';
    private static $securityLogFile = __DIR__ . '/storage/logs/security.log';
    
    public static function init() {
        self::createSecurityDirs();
        self::checkRateLimit();
        self::checkBlockedIPs();
        self::validateRequest();
    }
    
    private static function createSecurityDirs() {
        $dirs = [
            __DIR__ . '/storage/security',
            __DIR__ . '/storage/logs'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
        }
    }
    
    public static function checkRateLimit() {
        $ip = self::getClientIp();
        $now = time();
        $window = 60; // 1 minute
        $limit = 100; // requests per minute
        
        $rateLimits = self::loadJsonFile(self::$rateLimitFile, []);
        
        // Clean old entries
        $rateLimits = array_filter($rateLimits, function($data) use ($now, $window) {
            return ($now - $data['first_request']) < $window;
        });
        
        if (!isset($rateLimits[$ip])) {
            $rateLimits[$ip] = [
                'count' => 1,
                'first_request' => $now
            ];
        } else {
            $rateLimits[$ip]['count']++;
            
            if ($rateLimits[$ip]['count'] > $limit) {
                self::logSecurity("Rate limit exceeded for IP: $ip", 'RATE_LIMIT');
                self::blockIp($ip, 'Rate limit exceeded');
                self::sendHttpError(429, 'Too Many Requests');
            }
        }
        
        file_put_contents(self::$rateLimitFile, json_encode($rateLimits));
    }
    
    public static function checkBlockedIPs() {
        $ip = self::getClientIp();
        $blockedIps = self::loadJsonFile(self::$blockedIpsFile, []);
        
        if (isset($blockedIps[$ip])) {
            $blockData = $blockedIps[$ip];
            if (time() < $blockData['expires']) {
                self::logSecurity("Blocked IP attempted access: $ip", 'BLOCKED_ACCESS');
                self::sendHttpError(403, 'Access Denied');
            } else {
                // Unblock expired IPs
                unset($blockedIps[$ip]);
                file_put_contents(self::$blockedIpsFile, json_encode($blockedIps));
            }
        }
    }
    
    public static function validateRequest() {
        $suspicious = false;
        $ip = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Check for common attack patterns
        $maliciousPatterns = [
            '/\.\.\//i',           // Directory traversal
            '/union.*select/i',    // SQL injection
            '/<script/i',          // XSS
            '/javascript:/i',      // XSS
            '/vbscript:/i',        // XSS
            '/onload=/i',          // XSS
            '/onerror=/i',         // XSS
            '/eval\(/i',           // Code injection
            '/base64_decode/i',    // Code injection
            '/system\(/i',         // Command injection
            '/exec\(/i',           // Command injection
            '/shell_exec/i',       // Command injection
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $requestUri) || 
                preg_match($pattern, http_build_query($_GET)) ||
                preg_match($pattern, file_get_contents('php://input'))) {
                $suspicious = true;
                break;
            }
        }
        
        // Check for suspicious user agents
        $suspiciousAgents = [
            'sqlmap', 'nikto', 'nmap', 'masscan', 'nessus', 'openvas',
            'w3af', 'skipfish', 'whatweb', 'dirbuster', 'gobuster'
        ];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $suspicious = true;
                break;
            }
        }
        
        if ($suspicious) {
            self::logSecurity("Suspicious request from IP: $ip, UA: $userAgent, URI: $requestUri", 'SUSPICIOUS_REQUEST');
            self::blockIp($ip, 'Suspicious activity detected');
            self::sendHttpError(403, 'Forbidden');
        }
    }
    
    public static function validatePath($path) {
        // Prevent directory traversal
        if (preg_match('/\.\.|\/\/|\\\\/', $path)) {
            self::logSecurity("Directory traversal attempt: $path", 'PATH_TRAVERSAL');
            return false;
        }
        
        // Sanitize path
        $path = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $path);
        return $path;
    }
    
    public static function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                self::logSecurity("CSRF token validation failed", 'CSRF_FAIL');
                self::sendHttpError(403, 'CSRF Token Invalid');
            }
        }
    }
    
    private static function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    private static function blockIp($ip, $reason) {
        $blockedIps = self::loadJsonFile(self::$blockedIpsFile, []);
        $blockedIps[$ip] = [
            'reason' => $reason,
            'blocked_at' => time(),
            'expires' => time() + 3600 // 1 hour block
        ];
        file_put_contents(self::$blockedIpsFile, json_encode($blockedIps));
    }
    
    private static function logSecurity($message, $type = 'SECURITY') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'message' => $message
        ];
        
        file_put_contents(self::$securityLogFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    private static function loadJsonFile($file, $default = []) {
        return file_exists($file) ? json_decode(file_get_contents($file), true) ?? $default : $default;
    }
    
    private static function sendHttpError($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message, 'code' => $code]);
        exit;
    }
}

// ================================
// FILE SECURITY CLASS (keeping your existing class)
// ================================

class FileSecurityManager {
    private static $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'tiff',
        'pdf', 'doc', 'docx', 'txt', 'css', 'js',
        'mp4', 'mp3', 'avi', 'wav'
    ];
    
    private static $maxFileSize = 10485760; // 10MB
    
    public static function validateFile($filePath) {
        // Check if file exists
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowedExtensions)) {
            SecurityManager::logSecurity("Attempted access to disallowed file type: $filePath", 'FILE_ACCESS');
            return false;
        }
        
        // Check file size
        if (filesize($filePath) > self::$maxFileSize) {
            return false;
        }
        
        // Additional security checks for images
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'avif', 'tiff', 'webp'])) {
            $imageInfo = @getimagesize($filePath);
            if ($imageInfo === false) {
                return false;
            }
        }
        
        return true;
    }
    
    public static function secureServeFile($filePath, $filename = null) {
        if (!self::validateFile($filePath)) {
            SecurityManager::sendHttpError(403, 'File access denied');
        }
        
        $filename = $filename ?: basename($filePath);
        $mimeType = mime_content_type($filePath);
        
        // Security headers for file serving
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . addslashes($filename) . '"');
        header('Cache-Control: private, max-age=3600');
        
        // Stream file securely
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
            fclose($handle);
        }
    }
}

// ================================
// MAIN ROUTING LOGIC - UPDATED FOR CLEAN URLS
// ================================

// Initialize security
SecurityManager::init();

// Define secure base paths
define('BASE_PATH', __DIR__);
define('API_PATH', BASE_PATH . '/api');
define('USERS_PATH', BASE_PATH . '/users');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Get and validate request URI
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_uri = str_replace(basename($script_name), '', $script_name);
$clean_uri = str_replace($base_uri, '', $request_uri);
$clean_uri = strtok($clean_uri, '?');
$clean_uri = trim($clean_uri, '/');

// Validate and sanitize path
$clean_uri = SecurityManager::validatePath($clean_uri);
if ($clean_uri === false) {
    SecurityManager::sendHttpError(400, 'Invalid request path');
}

// Route requests securely with updated logic
switch (true) {
    // API Routes (unchanged)
    case str_starts_with($clean_uri, 'northcity/api'):
        forwardToApi();
        break;
    
    // Admin Routes (unchanged)
    case str_starts_with($clean_uri, 'admin'):
        forwardToAdmin();
        break;
    
    // Storage Routes (unchanged)
    case str_starts_with($clean_uri, 'storage'):
        serveSecureFile($clean_uri);
        break;
    
    // Direct frontend routes (clean URLs)
    case str_starts_with($clean_uri, 'northcity/'):
        $path = substr($clean_uri, 6); // Remove 'northcity/'
        $path = trim($path, '/');
        
        // Check if it's a frontend route
        if (isFrontendRoute($path)) {
            forwardToUserFrontend($path);
        } else {
            SecurityManager::sendHttpError(404, 'Not found');
        }
        break;
    
    // Root level routes (if accessed without northcity prefix)
    case empty($clean_uri):
        forwardToUserFrontend('');
        break;
    
    default:
        // Check if it's a direct frontend route without northcity prefix
        if (isFrontendRoute($clean_uri)) {
            forwardToUserFrontend($clean_uri);
        } else {
            forwardToUserFrontend($clean_uri);
        }
        break;
}

/**
 * Check if a path is a valid frontend route
 */
function isFrontendRoute($path) {
    if (empty($path)) return true; // Home page
    
    $segments = explode('/', $path);
    $firstSegment = $segments[0];
    
    // Define valid frontend routes
    $validRoutes = [
        'home', 'news', 'events', 'user', 'auth', 'search', 
        'about', 'contact', 'bookmarks', 'profile', 'dashboard', 'settings'
    ];
    
    return in_array($firstSegment, $validRoutes);
}

/**
 * Secure API forwarding (unchanged)
 */
function forwardToApi() {
    // Additional API security checks
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate content type for API requests
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!in_array($contentType, ['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data'])) {
            SecurityManager::sendHttpError(415, 'Unsupported Media Type');
        }
    }
    
    if (file_exists(API_PATH . '/index.php')) {
        chdir(API_PATH);
        require_once API_PATH . '/index.php';
    } else {
        SecurityManager::sendHttpError(404, 'API not found');
    }
}

/**
 * Secure admin forwarding (unchanged)
 */
function forwardToAdmin() {
    global $clean_uri;
    
    // Check if user is authenticated and has admin role
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
        // Redirect to login instead of showing error
        header('Location: /northcity/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    $admin_path = substr($clean_uri, 5);
    $admin_path = trim($admin_path, '/');
    
    if (file_exists(ADMIN_PATH . '/index.php')) {
        $_SERVER['ADMIN_PATH'] = $admin_path;
        chdir(ADMIN_PATH);
        require_once ADMIN_PATH . '/index.php';
    } else {
        SecurityManager::sendHttpError(404, 'Admin panel not found');
    }
}

/**
 * UPDATED: Secure user frontend forwarding with clean URLs
 */
function forwardToUserFrontend($path = '') {
    if (file_exists(USERS_PATH . '/index.php')) {
        // Set the frontend path for the users router
        $_SERVER['FRONTEND_PATH'] = $path;
        
        // Change to users directory
        $originalDir = getcwd();
        chdir(USERS_PATH);
        
        // Include the users router
        require_once USERS_PATH . '/index.php';
        
        // Restore original directory
        chdir($originalDir);
    } else {
        serveLandingPage();
    }
}

/**
 * Secure file serving (unchanged)
 */
function serveSecureFile($path) {
    $file_path = BASE_PATH . '/' . $path;
    
    // Security validation
    $real_path = realpath($file_path);
    $storage_real_path = realpath(STORAGE_PATH);
    
    if (!$real_path || !str_starts_with($real_path, $storage_real_path)) {
        SecurityManager::logSecurity("Attempted access outside storage: $path", 'FILE_ACCESS');
        SecurityManager::sendHttpError(403, 'Access denied');
    }
    
    FileSecurityManager::secureServeFile($file_path);
}

/**
 * Secure landing page (unchanged)
 */
function serveLandingPage() {
    // Additional security headers for HTML content
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
        <title>NorthCity - Secure News Platform</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6; color: #333;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh; display: flex; align-items: center; justify-content: center;
            }
            .container {
                max-width: 800px; margin: 0 auto; padding: 2rem; text-align: center;
                background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            h1 {
                font-size: 3rem; margin-bottom: 1rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text; -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .security-badge {
                display: inline-block; background: #28a745; color: white;
                padding: 0.5rem 1rem; border-radius: 25px; font-size: 0.9rem;
                margin: 1rem 0;
            }
            .features {
                display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem; margin: 3rem 0;
            }
            .feature {
                padding: 1.5rem; background: #f8f9fa; border-radius: 8px;
                border-left: 4px solid #667eea;
            }
            .btn {
                display: inline-block; padding: 12px 24px; text-decoration: none;
                border-radius: 6px; font-weight: 600; transition: all 0.3s ease;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; margin: 0.5rem;
            }
            .btn:hover { 
                transform: translateY(-2px); 
                box-shadow: 0 8px 20px rgba(0,0,0,0.2); 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒 NorthCity News Platform</h1>
            <div class="security-badge">
                <i>🛡️</i> Enterprise-Grade Security Enabled
            </div>
            
            <p>Access the platform with clean URLs:</p>
            
            <div style="margin: 2rem 0;">
                <a href="/northcity/news" class="btn">📰 Browse News</a>
                <a href="/northcity/events" class="btn">📅 Browse Events</a>
                <a href="/northcity/auth/login" class="btn">🔐 Sign In</a>
            </div>
            
            <div class="features">
                <div class="feature">
                    <h3>🔐 Advanced Security</h3>
                    <p>Rate limiting, IP blocking, CSRF protection, XSS prevention, and comprehensive logging.</p>
                </div>
                <div class="feature">
                    <h3>🛡️ Input Validation</h3>
                    <p>All inputs validated and sanitized. Path traversal and injection attack prevention.</p>
                </div>
                <div class="feature">
                    <h3>📊 Security Monitoring</h3>
                    <p>Real-time threat detection with automated blocking and detailed security logging.</p>
                </div>
                <div class="feature">
                    <h3>🔒 Clean URLs</h3>
                    <p>SEO-friendly URLs without exposing internal directory structure.</p>
                </div>
            </div>
        </div>
        
        <script nonce="<?php echo base64_encode(random_bytes(16)); ?>">
            // CSRF token for AJAX requests
            window.csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
            
            // Add CSRF token to all forms
            document.addEventListener('DOMContentLoaded', function() {
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    if (!form.querySelector('input[name="csrf_token"]')) {
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = 'csrf_token';
                        csrfInput.value = window.csrfToken;
                        form.appendChild(csrfInput);
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
}

// PHP 8 compatibility
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

?>