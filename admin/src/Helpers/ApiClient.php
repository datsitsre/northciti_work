<?php
// admin/src/Helpers/ApiClient.php - Fixed API Client Helper

class ApiClient
{
    private static $baseUrl;
    private static $authToken;

    public static function init()
    {
        self::$baseUrl = rtrim(API_BASE_URL, '/');
        self::$authToken = $_SESSION['auth_token'] ?? null;
    }

    public static function get($endpoint, $params = [], $token = null)
    {
        return self::request('GET', $endpoint, $params, null, $token);
    }

    public static function post($endpoint, $data = [], $token = null)
    {
        return self::request('POST', $endpoint, [], $data, $token);
    }

    public static function put($endpoint, $data = [], $token = null)
    {
        return self::request('PUT', $endpoint, [], $data, $token);
    }

    public static function delete($endpoint, $token = null)
    {
        return self::request('DELETE', $endpoint, [], null, $token);
    }

    public static function uploadFile($endpoint, $file, $metadata = [], $token = null)
    {
        // Refresh token from session
        self::$authToken = $_SESSION['auth_token'] ?? null;
        
        $url = self::$baseUrl . $endpoint;
        
        $postFields = [];
        $postFields['files'] = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
        
        foreach ($metadata as $key => $value) {
            $postFields[$key] = $value;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        $headers = ['Accept: application/json'];
        
        // Use provided token or session token
        $currentToken = $token ?: self::$authToken;
        
        if ($currentToken) {
            $headers[] = 'Authorization: Bearer ' . $currentToken;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Remove debugging code - log instead
        error_log("Upload Response HTTP Code: " . $httpCode);
        error_log("Upload Response Body: " . substr($response, 0, 500));
        
        if ($error = curl_error($ch)) {
            curl_close($ch);
            throw new \Exception('File upload failed: ' . $error);
        }
        
        curl_close($ch);
        
        return self::handleResponse($response, $httpCode);
    }

    private static function request($method, $endpoint, $params = [], $data = null, $token = null)
    {
        // Refresh token from session
        self::$authToken = $_SESSION['auth_token'] ?? null;
        
        $url = self::$baseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = ['Accept: application/json'];
        
        // Use provided token or session token
        $currentToken = $token ?: self::$authToken;

        // var_dump($currentToken);
        
        if ($currentToken) {
            $headers[] = 'Authorization: Bearer ' . $currentToken;
        }
        
        if ($data !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($error = curl_error($ch)) {
            curl_close($ch);
            throw new \Exception('API Request failed: ' . $error);
        }
        
        curl_close($ch);
        
        return self::handleResponse($response, $httpCode);
    }

    private static function handleResponse($response, $httpCode)
    {
        // Check if response looks like HTML (server error page)
        if (stripos(trim($response), '<!DOCTYPE') === 0 || stripos(trim($response), '<html') === 0) {
            error_log("Received HTML response instead of JSON. HTTP Code: {$httpCode}");
            error_log("Response body: " . substr($response, 0, 500));
            
            if ($httpCode >= 500) {
                throw new \Exception('Server error occurred. Please check server logs and try again.');
            } else {
                throw new \Exception('Unexpected HTML response from server. Expected JSON.');
            }
        }
        
        // Try to decode JSON response
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            error_log("Raw response: " . substr($response, 0, 500));
            throw new \Exception('Invalid response format from server: ' . json_last_error_msg());
        }

        // Handle authentication errors
        if ($httpCode === 401) {
            // Try to refresh token once
            if (self::refreshToken()) {
                throw new \Exception('Authentication token refreshed. Please retry your request.');
            } else {
                // Clear session and throw error
                session_destroy();
                throw new \Exception('Authentication failed. Please log in again.');
            }
        }

        if ($httpCode >= 400) {
            $message = $result['message'] ?? $result['error'] ?? 'API request failed with HTTP ' . $httpCode;
            throw new \Exception($message, $httpCode);
        }

        return $result;
    }

    private static function refreshToken()
    {
        try {
            if (class_exists('AuthController') && method_exists('AuthController', 'refreshApiToken')) {
                $result = AuthController::refreshApiToken();
                if ($result) {
                    self::$authToken = $_SESSION['auth_token'] ?? null;
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            error_log('Token refresh failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if we have authentication token
     */
    public static function isAuthenticated()
    {
        $currentToken = $_SESSION['auth_token'] ?? self::$authToken;
        return !empty($currentToken);
    }

    /**
     * Get current auth token
     */
    public static function getAuthToken()
    {
        return $_SESSION['auth_token'] ?? self::$authToken;
    }

    /**
     * Set auth token
     */
    public static function setAuthToken($token)
    {
        self::$authToken = $token;
        $_SESSION['auth_token'] = $token;
    }
}

// Initialize API Client
ApiClient::init();