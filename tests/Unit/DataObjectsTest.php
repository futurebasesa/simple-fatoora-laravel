<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SimpleFatoora\Laravel\Data\ArchiveRequest;
use SimpleFatoora\Laravel\Data\InvoiceData;
use SimpleFatoora\Laravel\Data\InvoiceLine;
use SimpleFatoora\Laravel\Data\ListOptions;
use SimpleFatoora\Laravel\Data\QuotationData;
use SimpleFatoora\Laravel\Enums\ArchiveFormat;
use SimpleFatoora\Laravel\Enums\DocumentType;
use SimpleFatoora\Laravel\Enums\Environment;

final class DataObjectsTest extends TestCase
{
    public function test_list_options_use_public_api_field_names(): void
    {
        self::assertSame([
            'page' => 2,
            'per_page' => 25,
            'search_key' => 'Acme',
        ], (new ListOptions(page: 2, perPage: 25, searchKey: 'Acme'))->toArray());
    }

    public function test_invoice_data_serializes_typed_lines_and_enums(): void
    {
        $payload = (new InvoiceData(
            type: DocumentType::CreditNote,
            lines: [new InvoiceLine('Returned item', 100, 1, vatPercentProduct: 15)],
            clientType: 2,
            taxesIncluded: false,
            dateTime: new DateTimeImmutable('2026-09-10 12:30:00'),
            parentInvoiceNumber: 'U123-1001',
            documentReason: 'Returned items',
        ))->toArray();

        self::assertSame(3, $payload['invoice_type']);
        self::assertSame(0, $payload['taxes_included']);
        self::assertSame('2026-09-10 12:30:00', $payload['date_time']);
        self::assertSame('U123-1001', $payload['parent_invoice_number']);
        self::assertSame('Returned items', $payload['document_reason']);
        self::assertSame(15.0, $payload['invoice_detail'][0]['vat_percent_product']);
    }

    public function test_quotation_payload_is_json_encoded_without_losing_unicode(): void
    {
        $payload = (new QuotationData(
            quotation: ['document_kind' => 'quotation', 'client_name' => 'شركة مثال'],
            environment: Environment::Sandbox,
        ))->toArray();

        self::assertSame('sandbox', $payload['environment']);
        self::assertStringContainsString('شركة مثال', $payload['json_data']);
    }

    public function test_archive_dates_and_document_types_are_serialized(): void
    {
        $payload = (new ArchiveRequest(
            ArchiveFormat::Xml,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-31'),
            [DocumentType::SimplifiedInvoice, DocumentType::CreditNote],
        ))->toArray();

        self::assertSame([
            'format' => 'xml',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'invoice_types' => [0, 3],
        ], $payload);
    }
}
