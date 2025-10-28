<?php

// api/src/Middleware/AuthMiddleware.php - JWT Authentication

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Services\JWTService;
use App\Exceptions\AuthenticationException;

class AuthMiddleware
{
    private JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function process(Request $request): Request
    {
        $token = $request->getBearerToken();
        
        if (!$token) {
            throw new AuthenticationException('Authentication token required');
        }

        try {
            $payload = $this->jwtService->validateToken($token);

            // Add user info to request
            $request->setRequestUser([
                'id' => $payload['user_id'],
                'role' => $payload['role'],
                'email' => $payload['email']
            ]);
            
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid or expired token');
        }

        return $request;
    }
}
