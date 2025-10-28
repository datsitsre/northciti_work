<?php

// api/src/Exceptions/AuthorizationException.php

declare(strict_types=1);

namespace App\Exceptions;

class AuthorizationException extends \Exception
{
    public function __construct(string $message = 'Access denied', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}