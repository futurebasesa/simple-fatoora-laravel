<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Data\ArchiveRequest;
use SimpleFatoora\Laravel\Enums\ArchiveFormat;
use SimpleFatoora\Laravel\Exceptions\DownloadException;
use SimpleFatoora\Laravel\Exceptions\ForbiddenException;
use SimpleFatoora\Laravel\Tests\TestCase;

final class DownloadTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_pdf_xml_and_archive_downloads_are_streamed_to_destinations(): void
    {
        Http::fake(static function (Request $request) {
            return match (true) {
                str_contains($request->url(), '/pdf/') => Http::response('%PDF-example', 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="invoice-123.pdf"',
                ]),
                str_contains($request->url(), '/xml/') => Http::response('<Invoice/>', 200, [
                    'Content-Type' => 'application/xml',
                ]),
                default => Http::response('PK-archive', 200, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => "attachment; filename*=UTF-8''invoices.zip",
                ]),
            };
        });

        $pdf = $this->path('invoice.pdf');
        $xml = $this->path('invoice.xml');
        $zip = $this->path('invoices.zip');
        $client = $this->client();

        $pdfResult = $client->documents()->downloadPdf(123, $pdf);
        $xmlResult = $client->documents()->downloadXml(123, $xml);
        $zipResult = $client->documents()->exportArchive(new ArchiveRequest(ArchiveFormat::Pdf), $zip);

        self::assertSame('%PDF-example', file_get_contents($pdf));
        self::assertSame('<Invoice/>', file_get_contents($xml));
        self::assertSame('PK-archive', file_get_contents($zip));
        self::assertSame('invoice-123.pdf', $pdfResult->filename);
        self::assertSame('invoices.zip', $zipResult->filename);
        self::assertGreaterThan(0, $xmlResult->size);
    }

    public function test_existing_destination_is_not_overwritten_without_explicit_opt_in(): void
    {
        $destination = $this->path('existing.pdf');
        file_put_contents($destination, 'existing');

        $this->expectException(DownloadException::class);
        $this->client()->documents()->downloadPdf(1, $destination);
    }

    public function test_json_download_errors_are_parsed_and_partial_files_are_removed(): void
    {
        Http::fake(['*' => Http::response([
            'status' => false,
            'response' => 'Invalid API key',
        ], 403, ['Content-Type' => 'application/json'])]);

        $destination = $this->path('failed.pdf');

        try {
            $this->client()->documents()->downloadPdf(1, $destination);
            self::fail('Expected a forbidden exception.');
        } catch (ForbiddenException) {
            self::assertFileDoesNotExist($destination);
        }
    }

    public function test_unexpected_download_content_is_rejected_and_removed(): void
    {
        Http::fake(['*' => Http::response('<html>Gateway page</html>', 200, ['Content-Type' => 'text/html'])]);
        $destination = $this->path('unexpected.pdf');

        try {
            $this->client()->documents()->downloadPdf(1, $destination);
            self::fail('Expected a download exception.');
        } catch (DownloadException $exception) {
            self::assertStringNotContainsString('Gateway page', $exception->getMessage());
            self::assertFileDoesNotExist($destination);
        }
    }

    public function test_downloads_request_the_expected_media_type(): void
    {
        Http::fake(['*' => Http::response('%PDF-example', 200, ['Content-Type' => 'application/octet-stream'])]);
        $destination = $this->path('accept.pdf');

        $this->client()->documents()->downloadPdf(1, $destination);

        Http::assertSent(static fn (Request $request): bool => $request->hasHeader('Accept', 'application/pdf'));
    }

    private function path(string $name): string
    {
        $path = sys_get_temp_dir().'/simplefatoora-'.bin2hex(random_bytes(5)).'-'.$name;
        $this->files[] = $path;

        return $path;
    }
}
