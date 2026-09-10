<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Enums\Environment;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class QuotationListOptions implements ArrayPayload
{
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
        public ?int $limit = null,
        public ?string $searchKey = null,
        public ?string $documentKind = 'quotation',
        public ?Environment $environment = null,
    ) {}

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'limit' => $this->limit,
            'search_key' => $this->searchKey,
            'document_kind' => $this->documentKind,
            'environment' => $this->environment?->value,
        ]);
    }
}
