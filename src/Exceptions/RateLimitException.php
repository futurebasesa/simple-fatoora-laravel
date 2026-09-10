<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Exceptions;

final class RateLimitException extends SimpleFatooraException
{
    /** @param array<string, mixed>|list<mixed>|null $details */
    public function __construct(
        string $message,
        public readonly ?int $retryAfterSeconds = null,
        ?array $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $details, $previous);
    }
}
