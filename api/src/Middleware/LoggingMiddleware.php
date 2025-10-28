<?php

// api/src/Middleware/LoggingMiddleware.php - Request Logging

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Database;

class LoggingMiddleware
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function process(Request $request): Request
    {
        $startTime = microtime(true);
        
        // Generate unique request ID
        $requestId = uniqid('req_', true);
        $request->setRequestId($requestId);
        
        // Log the request (you might want to do this after response in production)
        register_shutdown_function(function() use ($request, $startTime, $requestId) {
            $this->logRequest($request, $startTime, $requestId);
        });

        return $request;
    }

    private function logRequest(Request $request, float $startTime, string $requestId): void
    {
        $responseTime = (microtime(true) - $startTime) * 1000; // in milliseconds
        $responseCode = http_response_code() ?: 200;

        $logData = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'endpoint' => $request->getPath(),
            'ip_address' => $request->getIp(),
            'user_agent' => $request->getUserAgent(),
            'user_id' => $request->user['id'] ?? null,
            'api_key_id' => null, // TODO: implement if using API keys
            'request_data' => $request->isJson() ? json_encode($request->getData()) : null,
            'response_code' => $responseCode,
            'response_time_ms' => (int)$responseTime,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $columns = array_keys($logData);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO api_logs (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $this->db->execute($sql, array_values($logData));
        } catch (\Exception $e) {
            // Log to file if database logging fails
            error_log("Failed to log API request: " . $e->getMessage());
        }
    }
}