<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use InvalidArgumentException;
use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class InvoiceLine implements ArrayPayload
{
    public function __construct(
        public string $description,
        public float $unitPrice,
        public int $quantity,
        public ?float $vatPercentProduct = null,
        public ?float $vatPercent = null,
        public ?float $discountPercent = null,
    ) {
        if ($description === '') {
            throw new InvalidArgumentException('description cannot be empty.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('quantity must be at least 1.');
        }
    }

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'description' => $this->description,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'vat_percent_product' => $this->vatPercentProduct,
            'vat_percent' => $this->vatPercent,
            'discount_percent' => $this->discountPercent,
        ]);
    }
}
