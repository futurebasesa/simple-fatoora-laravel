<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Enums\DocumentType;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class InvoiceListOptions implements ArrayPayload
{
    /** @param list<DocumentType|int>|null $types */
    public function __construct(
        public ?int $page = null,
        public ?int $perPage = null,
        public ?int $limit = null,
        public ?string $searchKey = null,
        public ?bool $includeAllTypes = null,
        public ?array $types = null,
    ) {}

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'limit' => $this->limit,
            'search_key' => $this->searchKey,
            'include_all_invoice_types' => $this->includeAllTypes === null ? null : (int) $this->includeAllTypes,
            'invoice_types' => $this->types === null ? null : array_map(
                static fn (DocumentType|int $type): int => $type instanceof DocumentType ? $type->value : $type,
                $this->types,
            ),
        ]);
    }
}
