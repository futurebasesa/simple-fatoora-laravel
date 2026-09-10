<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Exceptions;

use RuntimeException;

class SimpleFatooraException extends RuntimeException
{
    /** @param array<string, mixed>|list<mixed>|null $details */
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?array $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }
}
