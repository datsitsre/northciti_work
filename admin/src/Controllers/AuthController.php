<?php

// admin/src/Controllers/AuthController.php - Updated Authentication Controller with API Token

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/LayoutHelper.php';
require_once __DIR__ . '/../Helpers/ApiClient.php';

class AuthController 
{
    public static function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (self::isLoggedIn()) {
            redirect(Router::url('dashboard'));
            return;
        }
        
        $error = '';
        $success = '';
        
        // Include the login view directly (no layout for login)
        include __DIR__ . '/../Views/auth/login.php';
    }
    
    public static function handleLogin()
    {
        $error = '';
        $email = '';
        
        try {
            
            // Check if this is actually a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Get form data
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';
            $rememberMe = isset($_POST['remember_me']);
            
            // Validate CSRF token first
            if (!validateCSRFToken($csrfToken)) {
                throw new Exception('Invalid security token. Please refresh the page and try again.');
            }
            
            // Validate input
            if (empty($email)) {
                throw new Exception('Email address is required');
            }
            
            if (empty($password)) {
                throw new Exception('Password is required');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Attempt local authentication first (for admin access verification)
            $user = self::authenticateUser($email, $password);
            
            if (!$user) {
                throw new Exception('Invalid email or password');
            }
            
            // Check if user is admin or super admin
            if (!in_array($user['role'], ['super_admin'])) {
                throw new Exception('Access denied. Admin privileges required.');
            }
            
            // Check account status
            if ($user['status'] !== 'active') {
                $statusMessages = [
                    'pending' => 'Your account is pending approval',
                    'suspended' => 'Your account has been suspended',
                    'banned' => 'Your account has been banned'
                ];
                throw new Exception($statusMessages[$user['status']] ?? 'Account is not active');
            }
            
            // Get API authentication token
            $apiTokens = self::getApiAuthTokens($email, $password);
            $apiToken = isset($apiTokens['access_token']) && !empty($apiTokens['access_token']) ? $apiTokens['access_token'] : null;

            if (!$apiToken) {
                throw new Exception('Failed to authenticate with API. Please try again.');
            }
            
            // Login successful - store both user and token
            self::loginUser($user, $apiTokens, $rememberMe);
            
            // Log the login
            self::logActivity($user['id'], 'admin_login');
            
            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Return JSON response for AJAX
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => Router::url('dashboard')
                ]);
                exit;
            }
            
            // Regular form submission - redirect
            redirect(Router::url('dashboard'));
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            
            // Log failed login attempt
            error_log("Admin login failed for email: {$email} - " . $error);
            
            // Check if this is an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Return JSON error response for AJAX
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $error
                ]);
                exit;
            }
            
            // Regular form submission - show login form with error
            include __DIR__ . '/../Views/auth/login.php';
        }
    }
    
    /**
     * Get API authentication token
     */
    private static function getApiAuthTokens($email, $password)
    {
        try {
            $apiUrl = rtrim(API_BASE_URL, '/') . '/auth/login';

            $postData = json_encode([
                'email' => $email,
                'password' => $password
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_error($ch)) {
                error_log('API authentication curl error: ' . curl_error($ch));
                curl_close($ch);
                return null;
            }
            
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("API authentication failed with HTTP code: {$httpCode}");
                return null;
            }
            
            $responseData = json_decode($response, true);
            
            if (isset($responseData['success']) && $responseData['success'] && isset($responseData['data']['tokens'])) {
                return $responseData['data']['tokens'];
            }
            
            error_log('API authentication response invalid: ' . $response);
            return null;
            
        } catch (Exception $e) {
            error_log('API authentication error: ' . $e->getMessage());
            return null;
        }
    }
    
    public static function logout()
    {
        $user = self::getCurrentUser();
        
        if ($user) {
            // Log the logout
            self::logActivity($user['id'], 'admin_logout');
            
            // Logout from API if we have a token
            if (isset($_SESSION['auth_token'])) {
                self::logoutFromApi($_SESSION['auth_token']);
            }
        }
        
        // Clear session
        session_destroy();
        
        // Redirect to login
        redirect(Router::url('login'));
    }
    
    /**
     * Logout from API
     */
    private static function logoutFromApi($token)
    {
        try {
            $apiUrl = rtrim(API_BASE_URL, '/') . '/auth/logout';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            
            curl_exec($ch);
            curl_close($ch);
            
        } catch (Exception $e) {
            error_log('API logout error: ' . $e->getMessage());
        }
    }
    
    public static function isLoggedIn()
    {
        return isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id']) && 
               isset($_SESSION['auth_token']) && !empty($_SESSION['auth_token']);
    }
    
    public static function getCurrentUser()
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        try {
            $userModel = new UserModel();
            return $userModel->findById($_SESSION['admin_user_id']);
        } catch (Exception $e) {
            error_log("Error fetching current user: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get current API token
     */
    public static function getApiToken()
    {
        return $_SESSION['auth_token'] ?? null;
    }

    /**
     * Get current refresh API token
     */
    public static function getApiRefreshToken()
    {
        return $_SESSION['auth_refresh_token'] ?? null;
    }
    
    /**
     * Refresh API token if needed
     */
    public static function refreshApiToken()
    {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        try {
            $user = self::getCurrentUser();
            if (!$user) {
                return false;
            }
            
            $apiUrl = rtrim(API_BASE_URL, '/') . '/auth/refresh';

            $postData = json_encode([
                'refresh_token' => $_SESSION['auth_refresh_token']
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['data']['tokens'])) {
                    $_SESSION['auth_token'] = $responseData['data']['tokens']['access_token'];
                    $_SESSION['auth_refresh_token'] = $responseData['data']['tokens']['refresh_token'];
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Token refresh error: ' . $e->getMessage());
            return false;
        }
    }
    
    private static function authenticateUser($email, $password)
    {
        try {
            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $userModel->updateLastLogin($user['id']);
                return $user;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return null;
        }
    }
    
    private static function loginUser($user, $apiTokens, $rememberMe = false)
    {
        // Regenerate session ID for security
        session_regenerate_id(true);            

        // Set session variables
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_user_email'] = $user['email'];
        $_SESSION['admin_user_role'] = $user['role'];
        $_SESSION['admin_login_time'] = time();
        $_SESSION['auth_token'] = $apiTokens['access_token']; // Store the API token
        $_SESSION['auth_refresh_token'] = $apiTokens['refresh_token']; // Store the API refresh token
        $_SESSION['user'] = $user;
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie('admin_remember_token', $token, $expiry, '/admin', '', true, true);
            
            // Store token in database (you might want to implement this)
            // $this->storeRememberToken($user['id'], $token, $expiry);
        }    
    }
    
    private static function logActivity($userId, $action, $details = null)
    {
        try {
            $db = Database::getInstance();
            
            $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, ip_address, user_agent, metadata, created_at) 
                    VALUES (?, ?, 'auth', 0, ?, ?, ?, NOW())";
            
            $metadata = json_encode([
                'action' => $action,
                'details' => $details,
                'timestamp' => time()
            ]);
            
            $db->execute($sql, [
                $userId,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $metadata
            ]);
            
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
    
    public static function requireAuth()
    {
        if (!self::isLoggedIn()) {
            redirect(Router::url('login'));
            exit;
        }
        
        // Check if token might be expired and try to refresh
        if (!self::refreshApiToken()) {
            // Token refresh failed, require re-login
            session_destroy();
            redirect(Router::url('login'));
            exit;
        }
    }
    
    public static function requireRole($requiredRole)
    {
        self::requireAuth();
        
        $user = self::getCurrentUser();
        if (!$user || $user['role'] !== $requiredRole) {
            LayoutHelper::addFlashMessage('Access denied. Insufficient privileges.', 'error');
            redirect(Router::url('dashboard'));
            exit;
        }
    }
}