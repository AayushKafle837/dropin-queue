<?php

declare(strict_types=1);

namespace App\Services;

final class ValidationException extends \InvalidArgumentException
{
    /** @param array<string, string> $errors field => message */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Validation failed');
    }
}
