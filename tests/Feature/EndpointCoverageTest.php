<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Data\ArchiveRequest;
use SimpleFatoora\Laravel\Enums\ArchiveFormat;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Tests\TestCase;

final class EndpointCoverageTest extends TestCase
{
    public function test_every_public_endpoint_is_exercised_through_a_resource_method(): void
    {
        Http::fake(static function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            if (str_contains($path, '/pdf/') || str_contains($path, '/quotation_pdf/')) {
                return Http::response('%PDF-example', 200, ['Content-Type' => 'application/pdf']);
            }
            if (str_contains($path, '/xml/')) {
                return Http::response('<Invoice/>', 200, ['Content-Type' => 'application/xml']);
            }
            if (str_ends_with($path, '/archive/export')) {
                return Http::response('PK-archive', 200, ['Content-Type' => 'application/zip']);
            }

            return Http::response(['status' => true, 'response' => ['id' => 123]], 200, [
                'Content-Type' => 'application/json',
            ]);
        });

        $client = $this->client();
        $account = $client->account();
        $account->createRegistrationIntent(['email_id' => 'developer@example.com'], 'journey-id');
        $account->sendEmailOtp('developer@example.com');
        $account->verifyEmailOtp('developer@example.com', 123456);
        $account->completeRegistration('registration-intent');
        $account->retrieveApiKeys('developer@example.com', 'password');
        $account->validateApiKey('candidate-key');
        $account->resetPassword('uuid', 'password', 'password');

        $logo = tempnam(sys_get_temp_dir(), 'simplefatoora-logo-');
        self::assertNotFalse($logo);
        file_put_contents($logo, 'image');

        $downloads = [];
        try {
            $client->profile()->get();
            $client->profile()->update(['company_name' => 'Example']);
            $client->profile()->uploadLogo($logo);

            $client->clients()->categories();
            $client->clients()->create(['first_name' => 'Client', 'mobile_number' => '500000000', 'country_code' => '+966']);
            $client->clients()->update(['id' => 123, 'first_name' => 'Client']);
            $client->clients()->find(123);
            $client->clients()->list();
            $client->clients()->delete(123);

            $client->products()->search('item', 10);
            $client->products()->list();

            $client->documents()->create(['invoice_type' => 0, 'invoice_detail' => [['description' => 'Item', 'unit_price' => 10, 'quantity' => 1]]]);
            $client->documents()->list();
            $client->documents()->find(123);
            $client->documents()->downloadPdf(123, $downloads[] = $this->downloadPath('invoice.pdf'));
            $client->documents()->downloadXml(123, $downloads[] = $this->downloadPath('invoice.xml'));
            $client->documents()->exportArchive(new ArchiveRequest(ArchiveFormat::Pdf), $downloads[] = $this->downloadPath('archive.zip'));

            $quotation = ['json_data' => '{"document_kind":"quotation"}'];
            $client->quotations()->create($quotation);
            $client->quotations()->update(['id' => 123] + $quotation);
            $client->quotations()->list();
            $client->quotations()->find(123);
            $client->quotations()->downloadPdf(123, $downloads[] = $this->downloadPath('quotation.pdf'));
            $client->quotations()->delete(123);

            $client->reports()->sales(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
            $client->reports()->vatReturn(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);

            $business = $this->zatcaBusinessPayload();
            $client->zatcaPhase2()->status();
            $client->zatcaPhase2()->saveDraft($business);
            $client->zatcaPhase2()->generateCsr($business);
            $client->zatcaPhase2()->submitOtp('123456');
            $client->zatcaPhase2()->retryCompliance();
            $client->zatcaPhase2()->refreshStatus();
            $client->zatcaPhase2()->renew();

            $actual = [];
            Http::assertSent(function (Request $request) use (&$actual): bool {
                $actual[] = [$request->method(), (string) parse_url($request->url(), PHP_URL_PATH)];

                return true;
            });

            $expected = array_map(
                static fn (Endpoint $endpoint): array => [
                    $endpoint->method()->value,
                    '/v1'.str_replace(
                        ['{client_id}', '{document_id}', '{quotation_id}'],
                        '123',
                        $endpoint->path(),
                    ),
                ],
                Endpoint::cases(),
            );

            sort($actual);
            sort($expected);

            self::assertCount(39, $actual);
            self::assertSame($expected, $actual);
        } finally {
            unlink($logo);
            foreach ($downloads as $download) {
                if (is_file($download)) {
                    unlink($download);
                }
            }
        }
    }

    private function downloadPath(string $suffix): string
    {
        return sys_get_temp_dir().'/simplefatoora-'.bin2hex(random_bytes(6)).'-'.$suffix;
    }

    /** @return array<string, string> */
    private function zatcaBusinessPayload(): array
    {
        return [
            'source_channel' => 'api',
            'company_name' => 'Example Company',
            'organization_name' => 'Example Company',
            'organization_identifier' => '1010000000',
            'organization_unit_name' => 'Main branch',
            'company_registration_number' => '1010000000',
            'company_vat_number' => '300000000000003',
            'industry_business_category' => 'Technology',
            'street_name' => 'Example Street',
            'building_number' => '1234',
            'district' => 'Example District',
            'city' => 'Riyadh',
            'postal_code' => '12345',
            'country_name' => 'Saudi Arabia',
        ];
    }
}
