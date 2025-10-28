<?php

// api/src/Middleware/SecurityMiddleware.php

declare(strict_types=1);

namespace App\Middleware;

class SecurityMiddleware {

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function hashPassword($password) {
        return password_hash($password . $this->config['PASSWORD_PEPPER'], PASSWORD_BCRYPT);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password . $this->config['PASSWORD_PEPPER'], $hash);
    }
}