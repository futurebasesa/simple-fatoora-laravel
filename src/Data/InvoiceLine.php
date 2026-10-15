<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use InvalidArgumentException;
use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class InvoiceLine implements ArrayPayload
{
    /** @param array{reason_code: string, category?: string, reason?: string}|null $zeroVat */
    public function __construct(
        public string $description,
        public float $unitPrice,
        public int|float|string $quantity,
        public ?float $vatPercentProduct = null,
        public ?float $vatPercent = null,
        public ?float $discountPercent = null,
        public ?array $zeroVat = null,
        public ?string $productId = null,
        public ?int $discountType = null,
        public ?float $discountValue = null,
        public ?float $totalDiscount = null,
    ) {
        if ($description === '') {
            throw new InvalidArgumentException('description cannot be empty.');
        }

        if (! is_numeric($quantity) || ! is_finite((float) $quantity) || (float) $quantity < 0.000001 || (float) $quantity >= 1000000000000 || round((float) $quantity, 6) !== (float) $quantity) {
            throw new InvalidArgumentException('quantity must be positive, less than 1000000000000, with at most six decimal places.');
        }
    }

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'zero_vat' => $this->zeroVat,
            'product_id' => $this->productId,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'total_discount' => $this->totalDiscount,

            'description' => $this->description,
            'unit_price' => $this->unitPrice,
            'quantity' => $this->quantity,
            'vat_percent_product' => $this->vatPercentProduct,
            'vat_percent' => $this->vatPercent,
            'discount_percent' => $this->discountPercent,
        ]);
    }
}
