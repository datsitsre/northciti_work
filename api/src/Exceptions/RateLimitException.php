<?php

// api/src/Exceptions/RateLimitException.php

declare(strict_types=1);

namespace App\Exceptions;

class RateLimitException extends \Exception
{
    public function __construct(string $message = 'Rate limit exceeded', int $code = 429)
    {
        parent::__construct($message, $code);
    }
}
