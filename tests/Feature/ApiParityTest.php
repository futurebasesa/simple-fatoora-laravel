<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Data\InvoiceData;
use SimpleFatoora\Laravel\Data\InvoiceLine;
use SimpleFatoora\Laravel\Enums\DocumentType;
use SimpleFatoora\Laravel\Exceptions\ConflictException;
use SimpleFatoora\Laravel\Tests\TestCase;

final class ApiParityTest extends TestCase
{
    public function test_fractional_payload_and_idempotency_header_are_preserved(): void
    {
        $this->fakeJsonSuccess(['id' => 123]);
        $document = new InvoiceData(DocumentType::SimplifiedInvoice,
            [new InvoiceLine('Qualifying item', 100, 1.5, vatPercent: 0, zeroVat: ['reason_code' => 'VATEX-SA-35'])],
            zeroVatVersion: 1, transactionDateMode: 'previous', transactionDate: '2026-09-15',
            notes: 'Document note', buyerCountry: 'SA');
        $this->client()->documents()->create($document, idempotencyKey: 'order-1001');
        Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'order-1001')
            && $request['invoice_detail'][0]['quantity'] === 1.5
            && $request['invoice_detail'][0]['zero_vat']['reason_code'] === 'VATEX-SA-35'
            && $request['transaction_date'] === '2026-09-15');
    }

    public function test_conflicts_are_not_retried(): void
    {
        Http::fake(['*' => Http::response(['status' => false, 'message' => 'Request is processing', 'response' => ['invoice_id' => 123]], 409)]);
        try {
            $this->client()->documents()->create(['invoice_type' => 0], 'order-1001');
            self::fail('Expected a conflict.');
        } catch (ConflictException $exception) {
            Http::assertSentCount(1);
        }
    }

    public function test_archive_metadata_is_available_without_opening_the_zip(): void
    {
        Http::fake(['*' => Http::response('PK-example', 200, ['Content-Type' => 'application/zip',
            'X-SF-Archive-Next' => 'opaque-cursor', 'X-SF-Archive-Part' => '2',
            'X-SF-Archive-Exported' => '249', 'X-SF-Archive-Skipped' => '1'])]);
        $path = sys_get_temp_dir().'/sf-parity-'.bin2hex(random_bytes(8)).'.zip';
        try {
            $part = $this->client()->documents()->exportArchive(['format' => 'pdf', 'cursor' => 'previous'], $path);
            self::assertSame('opaque-cursor', $part->nextCursor);
            self::assertSame(2, $part->part);
            self::assertSame(249, $part->exported);
            self::assertSame(1, $part->skipped);
            self::assertFalse($part->complete);
            Http::assertSent(fn ($request) => $request['cursor'] === 'previous');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_clearing_default_sends_an_explicit_null(): void
    {
        $this->fakeJsonSuccess();
        $this->client()->profile()->clearZeroVatDefault();
        Http::assertSent(fn ($request) => array_key_exists('zero_vat_default', $request->data()) && $request['zero_vat_default'] === null);
    }

    public function test_fractional_precision_rejects_invalid_values(): void
    {
        foreach ([0, -1, 0.0000001, 1.1234567, INF] as $quantity) {
            try {
                new InvoiceLine('Item', 100, $quantity);
                self::fail('Invalid quantity accepted.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('quantity', $exception->getMessage());
            }
        }
        self::assertSame(0.000001, (new InvoiceLine('Item', 100, 0.000001))->toArray()['quantity']);
    }
}
