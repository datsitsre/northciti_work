<?php

// api/src/Core/Request.php - Fixed HTTP Request Handler

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $headers;
    private array $query;
    private array $data;
    private array $requestUser;
    private array $files;
    private array $server;
    private array $user;
    private array $bodyObject;
    private ?string $body;
    private string $requestId;

    public function __construct(
        string $method,
        string $uri,
        array $headers = [],
        array $query = [],
        array $data = [],
        array $user = [],
        array $files = [],
        array $server = [],
        ?string $body = null
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->headers = $headers;
        $this->query = $query;
        $this->data = $data;
        $this->user = $user;
        $this->files = $files;
        $this->server = $server;
        $this->body = $body;
        $this->bodyObject = $this->parseBody();
    }

    public static function createFromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }

        // Add content-type and content-length headers if present
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        $body = file_get_contents('php://input');
        $data = $_POST;

        // Parse JSON body for non-form requests
        if (isset($headers['content-type']) && 
            strpos($headers['content-type'], 'application/json') !== false) {
            $jsonData = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $jsonData;
            }
        }

        // Enhanced query parameter parsing
        $queryParams = [];
        
        // First, use $_GET as primary source
        if (!empty($_GET)) {
            $queryParams = $_GET;
        }
        
        // Also parse from REQUEST_URI in case $_GET is not populated properly
        if (isset($_SERVER['REQUEST_URI'])) {
            $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $additionalParams);
                $queryParams = array_merge($queryParams, $additionalParams);
            }
        }
        
        // Parse from QUERY_STRING as fallback
        if (empty($queryParams) && isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queryParams);
        }

        return new self(
            $_SERVER['REQUEST_METHOD'],  // method
            $_SERVER['REQUEST_URI'],     // uri
            $headers,                    // headers
            $queryParams,                // query - enhanced parsing
            $data,                       // data
            [],                          // user (empty array initially)
            $_FILES,                     // files
            $_SERVER,                    // server
            $body                        // body
        );
    }

    public function setRequestUser(array $user): void
    {
        $this->requestUser = $user;
        $this->user = $user;
    }

    public function getRequestUser(): array
    {
        return $this->requestUser ?? [];
    }

    public function getUser(): array
    {
        return $this->user ?? [];
    }

    public function setUser(array $user): void
    {
        $this->user = $user;
        $this->requestUser = $user; // Keep both in sync
    }

    public function setRequestId(string $id): void
    {
        $this->requestId = $id;
    }

    public function getRequestId(): string
    {
        return $this->requestId ?? '';
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Enhanced query parameter getter
     */
    public function getQuery(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Check if query parameter exists
     */
    public function hasQuery(string $key): bool
    {
        return isset($this->query[$key]);
    }

    /**
     * Get query parameter as integer
     */
    public function getQueryInt(string $key, int $default = 0): int
    {
        $value = $this->getQuery($key, $default);
        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * Get query parameter as boolean
     */
    public function getQueryBool(string $key, bool $default = false): bool
    {
        $value = $this->getQuery($key);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
    }

    public function getData(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }
        return $this->data[$key] ?? $default;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function getBody(): ?array
    {
        $object = !empty($this->body) ? json_decode($this->body, true) : [];
        return $object;
    }

    public function getBodyObject(): ?array
    {
        return $this->bodyObject ?? [];
    }

    public function getIp(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] ??
               $this->server['HTTP_X_REAL_IP'] ??
               $this->server['REMOTE_ADDR'] ??
               'unknown';
    }

    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function getBearerToken(): ?string
    {
        $header = $this->getHeader('authorization');
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function isJson(): bool
    {
        return strpos($this->getHeader('content-type') ?? '', 'application/json') !== false;
    }

    public function isAjax(): bool
    {
        return strtolower($this->getHeader('x-requested-with') ?? '') === 'xmlhttprequest';
    }

    private function parseBody(): array 
    {
        if ($this->method === 'GET') {
            return [];
        }

        $contentType = '';
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'];
        } elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
        }

        $parsed_body = [];
        
        if ($contentType && strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $parsed_body = json_decode($input, true) ?? [];
        } elseif ($contentType && strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $parsed_body = $_POST;
        }

        return $parsed_body;
    }

    /**
     * Parse multipart/form-data from raw body and return clean array
     */
    public function getMultipartData(): array
    {
        // First check if we have regular POST data (most common case)
        if (!empty($_POST)) {
            return $_POST;
        }

        // If no body, return empty
        if (empty($this->body)) {
            return [];
        }

        // Get content type from multiple sources
        $contentType = $this->getHeader('content-type') ?? 
                       $this->server['CONTENT_TYPE'] ?? 
                       $this->server['HTTP_CONTENT_TYPE'] ?? '';

        // Check if it's multipart data by looking at the body content
        if (empty($contentType) && strpos($this->body, 'WebKitFormBoundary') !== false) {
            // Extract boundary from body for WebKit browsers
            if (preg_match('/------WebKitFormBoundary([a-zA-Z0-9]+)/', $this->body, $matches)) {
                $boundary = '----WebKitFormBoundary' . $matches[1];
                return $this->parseMultipartBody($boundary);
            }
        }

        // Extract boundary from content type header
        if (!empty($contentType) && strpos($contentType, 'multipart/form-data') !== false) {
            if (preg_match('/boundary=([^;]+)/', $contentType, $matches)) {
                $boundary = trim($matches[1], '"');
                return $this->parseMultipartBody($boundary);
            }
        }

        return [];
    }

    /**
     * Parse multipart body with given boundary
     */
    private function parseMultipartBody(string $boundary): array
    {
        $parts = explode('--' . $boundary, $this->body);
        $data = [];

        foreach ($parts as $part) {
            $part = trim($part);
            
            // Skip empty parts and boundary markers
            if (empty($part) || $part === '--') {
                continue;
            }

            // Find the double line break that separates headers from content
            $headerEndPos = strpos($part, "\r\n\r\n");
            if ($headerEndPos === false) {
                $headerEndPos = strpos($part, "\n\n");
                if ($headerEndPos === false) {
                    continue;
                }
                $headerEndLength = 2;
            } else {
                $headerEndLength = 4;
            }

            $headers = substr($part, 0, $headerEndPos);
            $content = substr($part, $headerEndPos + $headerEndLength);

            // Remove trailing boundary markers and whitespace
            $content = rtrim($content, "\r\n-");

            // Parse the Content-Disposition header to get field name
            if (preg_match('/Content-Disposition:\s*form-data;\s*name="([^"]+)"/', $headers, $nameMatches)) {
                $fieldName = $nameMatches[1];
                
                // Check if it's a file field
                if (preg_match('/filename="([^"]*)"/', $headers, $filenameMatches)) {
                    // Handle file upload
                    $filename = $filenameMatches[1];
                    if (!empty($filename)) {
                        $data[$fieldName] = [
                            'name' => $filename,
                            'content' => $content,
                            'size' => strlen($content),
                            'type' => $this->extractContentType($headers)
                        ];
                    } else {
                        // Empty file field
                        $data[$fieldName] = '';
                    }
                } else {
                    // Regular form field
                    $data[$fieldName] = $content;
                }
            }
        }

        return $data;
    }

    /**
     * Extract Content-Type from headers
     */
    private function extractContentType(string $headers): string
    {
        if (preg_match('/Content-Type:\s*([^\r\n]+)/', $headers, $matches)) {
            return trim($matches[1]);
        }
        return 'application/octet-stream';
    }

    /**
     * Get all request data with multipart support
     */
    public function getAllData(): array
    {
        // Try multipart first if we have a body with boundary markers
        if (!empty($this->body) && strpos($this->body, 'WebKitFormBoundary') !== false) {
            $multipartData = $this->getMultipartData();
            if (!empty($multipartData)) {
                return $multipartData;
            }
        }

        // Check content type
        $contentType = $this->getHeader('content-type') ?? 
                       $this->server['CONTENT_TYPE'] ?? 
                       $this->server['HTTP_CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            return $this->getMultipartData();
        } elseif (strpos($contentType, 'application/json') !== false) {
            return $this->getBodyObject();
        } else {
            return $this->getData();
        }
    }

    /**
     * Enhanced debug method
     */
    public function getDebugInfo(): array
    {
        return [
            'method' => $this->getMethod(),
            'uri' => $this->getUri(),
            'path' => $this->getPath(),
            'query_string' => $this->server['QUERY_STRING'] ?? null,
            'request_uri' => $this->server['REQUEST_URI'] ?? null,
            'parsed_query' => $this->query,
            'get_superglobal' => $_GET,
            'content_type_header' => $this->getHeader('content-type'),
            'server_content_type' => $this->server['CONTENT_TYPE'] ?? null,
            'http_content_type' => $this->server['HTTP_CONTENT_TYPE'] ?? null,
            'has_webkit_boundary' => strpos($this->body ?? '', 'WebKitFormBoundary') !== false,
            'post_data' => $_POST,
            'get_data' => $this->getData(),
            'body_object' => $this->getBodyObject(),
            'multipart_data' => $this->getMultipartData(),
            'all_data' => $this->getAllData(),
            'has_body' => !empty($this->body),
            'body_length' => strlen($this->body ?? ''),
            'body_preview' => substr($this->body ?? '', 0, 500)
        ];
    }

    public function debugQuery(Request $request): array
    {
        $debug = [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'path' => $request->getPath(),
            
            // Query parameter debugging
            'query_all' => $request->getQuery(),
            'query_page' => $request->getQuery('page'),
            'query_per_page' => $request->getQuery('per_page'),
            'query_status' => $request->getQuery('status'),
            'query_search' => $request->getQuery('search'),
            
            // Helper methods
            'query_page_int' => $request->getQueryInt('page', 1),
            'query_per_page_int' => $request->getQueryInt('per_page', 10),
            'has_page' => $request->hasQuery('page'),
            'has_status' => $request->hasQuery('status'),
            
            // Server variables for debugging
            'server_query_string' => $_SERVER['QUERY_STRING'] ?? null,
            'server_request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'get_superglobal' => $_GET,
            
            // Parse URL manually
            'parsed_url' => parse_url($_SERVER['REQUEST_URI'] ?? ''),
        ];

        return $debug;
    }
}