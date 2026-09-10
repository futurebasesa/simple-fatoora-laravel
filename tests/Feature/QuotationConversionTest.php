<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Data\InvoiceData;
use SimpleFatoora\Laravel\Data\InvoiceLine;
use SimpleFatoora\Laravel\Enums\DocumentType;
use SimpleFatoora\Laravel\Tests\TestCase;

final class QuotationConversionTest extends TestCase
{
    public function test_conversion_uses_the_document_endpoint_with_duplicate_protection_fields(): void
    {
        $this->fakeJsonSuccess(['id' => 456]);

        $response = $this->client()->quotations()->convert(123, new InvoiceData(
            type: DocumentType::SimplifiedInvoice,
            lines: [new InvoiceLine('Consulting', 100, 1)],
        ));

        self::assertIsArray($response->response);
        self::assertSame(456, $response->response['id']);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.example.test/v1/invoice/create'
                && $request['draft_id'] === 123
                && $request['created_type'] === 'quotation'
                && $request['source_uid'] === 'quotation:123';
        });
    }

    public function test_conversion_owns_protected_fields_even_for_array_input(): void
    {
        $this->fakeJsonSuccess(['id' => 456]);

        $this->client()->quotations()->convert(123, [
            'invoice_type' => 0,
            'invoice_detail' => [['description' => 'Consulting', 'unit_price' => 100, 'quantity' => 1]],
            'draft_id' => 999,
            'created_type' => 'api',
            'source_uid' => 'unsafe-override',
        ]);

        Http::assertSent(fn (Request $request): bool => $request['draft_id'] === 123
            && $request['created_type'] === 'quotation'
            && $request['source_uid'] === 'quotation:123');
    }
}
