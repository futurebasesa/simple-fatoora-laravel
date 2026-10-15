<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use DateTimeInterface;
use InvalidArgumentException;
use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Enums\DocumentType;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class InvoiceData implements ArrayPayload
{
    /** @param non-empty-list<InvoiceLine|array<string, mixed>> $lines */
    public function __construct(
        public DocumentType $type,
        public array $lines,
        public ?int $clientType = null,
        public ?bool $taxesIncluded = null,
        public ?int $clientId = null,
        public ?string $clientName = null,
        public ?string $clientAddress = null,
        public ?string $clientEmail = null,
        public ?string $clientVatNumber = null,
        public DateTimeInterface|string|null $dateTime = null,
        public ?string $referenceNumber = null,
        public ?string $parentInvoiceNumber = null,
        public ?string $documentReason = null,
        public ?string $createdType = null,
        public ?string $sourceUid = null,
        public ?int $draftId = null,
        public ?int $zeroVatVersion = null,
        public ?string $transactionDateMode = null,
        public ?string $transactionDate = null,
        public ?string $buyerCountry = null,
        public ?string $streetName = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $buildingNumber = null,
        public ?string $postalCode = null,
        public ?string $clientCrNumber = null,
        public ?string $clientContactNumber = null,
        public ?string $clientCountryCode = null,
        public ?string $currency = null,
        public ?float $shippingCharge = null,
        public ?int $shippingTaxable = null,
        public ?float $extraDiscount = null,
        public ?string $notes = null,
        public ?string $footerText = null,
    ) {
        if ($lines === []) {
            throw new InvalidArgumentException('At least one invoice line is required.');
        }

        if ($clientType !== null && ! in_array($clientType, [0, 1, 2], true)) {
            throw new InvalidArgumentException('clientType must be 0, 1, or 2.');
        }
    }

    public function toArray(): array
    {
        $dateTime = $this->dateTime instanceof DateTimeInterface
            ? $this->dateTime->format('Y-m-d H:i:s')
            : $this->dateTime;

        return Payload::withoutNulls([
            'zero_vat_version' => $this->zeroVatVersion,
            'transaction_date_mode' => $this->transactionDateMode,
            'transaction_date' => $this->transactionDate,
            'buyer_country' => $this->buyerCountry,
            'street_name' => $this->streetName,
            'city' => $this->city,
            'district' => $this->district,
            'building_number' => $this->buildingNumber,
            'postal_code' => $this->postalCode,
            'client_cr_number' => $this->clientCrNumber,
            'client_contact_number' => $this->clientContactNumber,
            'client_country_code' => $this->clientCountryCode,
            'currency' => $this->currency,
            'shipping_charge' => $this->shippingCharge,
            'shipping_taxable' => $this->shippingTaxable,
            'extra_discount' => $this->extraDiscount,
            'notes' => $this->notes,
            'footer_text' => $this->footerText,

            'invoice_type' => $this->type->value,
            'client_type' => $this->clientType,
            'taxes_included' => $this->taxesIncluded === null ? null : (int) $this->taxesIncluded,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
            'client_address' => $this->clientAddress,
            'client_email_id' => $this->clientEmail,
            'client_vat_number' => $this->clientVatNumber,
            'date_time' => $dateTime,
            'reference_number' => $this->referenceNumber,
            'parent_invoice_number' => $this->parentInvoiceNumber,
            'document_reason' => $this->documentReason,
            'created_type' => $this->createdType,
            'source_uid' => $this->sourceUid,
            'draft_id' => $this->draftId,
            'invoice_detail' => array_map(
                static fn (InvoiceLine|array $line): array => $line instanceof InvoiceLine ? $line->toArray() : $line,
                $this->lines,
            ),
        ]);
    }
}
