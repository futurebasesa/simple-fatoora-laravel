<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use JsonException;
use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Enums\Environment;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class QuotationData implements ArrayPayload
{
    /** @param array<string, mixed> $quotation */
    public function __construct(
        public array $quotation,
        public ?int $id = null,
        public ?Environment $environment = null,
    ) {}

    /** @throws JsonException */
    public function toArray(): array
    {
        return Payload::withoutNulls([
            'id' => $this->id,
            'json_data' => json_encode($this->quotation, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'environment' => $this->environment?->value,
        ]);
    }
}
