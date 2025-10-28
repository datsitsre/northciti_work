<?php
// api/src/Exceptions/ValidationException.php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(string $message, array $errors = [], int $code = 400)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}