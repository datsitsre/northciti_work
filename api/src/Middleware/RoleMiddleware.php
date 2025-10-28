<?php

// api/src/Middleware/RoleMiddleware.php - Role-based Access Control

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Exceptions\AuthorizationException;

class RoleMiddleware
{
    private array $requiredRoles;

    public function __construct(array $requiredRoles = [])
    {
        $this->requiredRoles = $requiredRoles;
    }

    public function process(Request $request): Request
    {
        if (empty($this->requiredRoles)) {
            return $request;
        }

        $user = $request->getRequestUser();

        if (!isset($user)) {
            throw new AuthorizationException('User authentication required');
        }

        $userRole = $user['role'];
        
        if (!in_array($userRole, $this->requiredRoles)) {
            throw new AuthorizationException('Insufficient permissions');
        }

        return $request;
    }

    public static function requireRole(string ...$roles): self
    {
        return new self($roles);
    }
}
