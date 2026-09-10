<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

final readonly class ApiResponse
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public bool $successful,
        public mixed $response,
        public ?string $message,
        public array $raw,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            successful: (bool) ($payload['status'] ?? true),
            response: $payload['response'] ?? $payload,
            message: isset($payload['message']) ? (string) $payload['message'] : null,
            raw: $payload,
        );
    }
}
