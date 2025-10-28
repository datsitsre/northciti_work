<?php

// api/src/Middleware/CorsMiddleware.php - CORS Handler

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware
{
    public function process(Request $request): Request
    {
        $origin = $request->getHeader('origin');
        $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*');

        // Set CORS headers
        if ($origin && (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins))) {
            header("Access-Control-Allow-Origin: {$origin}");
        } elseif (in_array('*', $allowedOrigins)) {
            header("Access-Control-Allow-Origin: *");
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return $request;
    }
}