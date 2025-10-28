<?php

// api/src/Exceptions/AuthenticationException.php

declare(strict_types=1);

namespace App\Exceptions;

class AuthenticationException extends \Exception
{
    public function __construct(string $message = 'Authentication required', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
