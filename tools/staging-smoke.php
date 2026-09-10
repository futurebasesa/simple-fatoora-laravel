<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use SimpleFatoora\Laravel\Data\ArchiveRequest;
use SimpleFatoora\Laravel\Data\ClientCreateData;
use SimpleFatoora\Laravel\Data\ClientUpdateData;
use SimpleFatoora\Laravel\Data\DateRange;
use SimpleFatoora\Laravel\Data\InvoiceData;
use SimpleFatoora\Laravel\Data\InvoiceLine;
use SimpleFatoora\Laravel\Data\InvoiceListOptions;
use SimpleFatoora\Laravel\Data\ListOptions;
use SimpleFatoora\Laravel\Data\QuotationData;
use SimpleFatoora\Laravel\Data\QuotationListOptions;
use SimpleFatoora\Laravel\Enums\ArchiveFormat;
use SimpleFatoora\Laravel\Enums\DocumentType;
use SimpleFatoora\Laravel\Enums\Environment;
use SimpleFatoora\Laravel\Exceptions\ApiException;
use SimpleFatoora\Laravel\Exceptions\AuthenticationException;
use SimpleFatoora\Laravel\Exceptions\ForbiddenException;
use SimpleFatoora\Laravel\Exceptions\UnauthorizedException;
use SimpleFatoora\Laravel\Http\ClientConfig;
use SimpleFatoora\Laravel\Http\HttpTransport;
use SimpleFatoora\Laravel\SimpleFatooraClient;
use SimpleFatoora\Laravel\Support\SystemSleeper;

require dirname(__DIR__).'/vendor/autoload.php';

function smokeStep(string $message): void
{
    fwrite(STDOUT, "PASS {$message}\n");
}

function smokeAssert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function responseArray(mixed $response, string $step): array
{
    smokeAssert(is_array($response), "{$step} did not return an object.");

    return $response;
}

/**
 * @param  array<string, mixed>  $response
 * @param  list<string>  $keys
 */
function responseInt(array $response, array $keys, string $step): int
{
    foreach ($keys as $key) {
        if (isset($response[$key]) && is_numeric($response[$key])) {
            return (int) $response[$key];
        }
    }

    throw new RuntimeException("{$step} did not return its identifier.");
}

/** @return array{base_url: string, api_key: string, sandbox_api_key: string} */
function readProtectedConfiguration(): array
{
    $configuration = json_decode(stream_get_contents(STDIN) ?: '', true);
    smokeAssert(is_array($configuration), 'Protected staging configuration is missing.');

    foreach (['base_url', 'api_key', 'sandbox_api_key'] as $key) {
        smokeAssert(
            isset($configuration[$key]) && is_string($configuration[$key]) && trim($configuration[$key]) !== '',
            'Protected staging configuration is incomplete.',
        );
    }

    /** @var array{base_url: string, api_key: string, sandbox_api_key: string} $configuration */
    return $configuration;
}

/** @param array{base_url: string, api_key: string, sandbox_api_key: string} $configuration */
function clientFromConfiguration(array $configuration): SimpleFatooraClient
{
    $config = new ClientConfig(
        baseUrl: rtrim($configuration['base_url'], '/'),
        environment: Environment::Live,
        apiKey: $configuration['api_key'],
        sandboxApiKey: $configuration['sandbox_api_key'],
        timeout: 30,
        connectTimeout: 10,
        retries: 2,
        retryDelayMilliseconds: 250,
        maxRetryDelayMilliseconds: 1000,
    );

    return new SimpleFatooraClient(
        new HttpTransport(new Factory, $config, new SystemSleeper),
        $config,
    );
}

/** @return array<string, mixed> */
function waitForDocument(SimpleFatooraClient $client, int $documentId): array
{
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $latest = responseArray($client->documents()->find($documentId)->response, 'document retrieval');
        if (($latest['pdf_ready'] ?? false) && ($latest['xml_available'] ?? false)) {
            return $latest;
        }

        sleep(1);
    }

    throw new RuntimeException('The staging document did not become downloadable in time.');
}

/** @param callable(): mixed $download */
function waitForDownload(callable $download): void
{
    for ($attempt = 0; $attempt < 30; $attempt++) {
        try {
            $download();

            return;
        } catch (ApiException) {
            sleep(1);
        }
    }

    throw new RuntimeException('The staging download did not become available in time.');
}

$step = 'configuration';
$liveClientId = null;
$sandboxClientId = null;
$quotationId = null;
$profileOriginalFooter = '';
$profileChanged = false;
$temporaryDirectory = sys_get_temp_dir().'/simple-fatoora-staging-smoke-'.bin2hex(random_bytes(8));
$retainedDocuments = 0;
$exitCode = 0;

try {
    $configuration = readProtectedConfiguration();
    $client = clientFromConfiguration($configuration);
    $live = $client->forLive();
    $sandbox = $client->forSandbox();
    $marker = 'laravel-sdk-smoke-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
    $today = gmdate('Y-m-d');
    smokeAssert(mkdir($temporaryDirectory, 0700), 'Unable to create the private download directory.');

    $step = 'live and sandbox authentication';
    $liveProfile = responseArray($live->profile()->get()->response, 'live profile');
    responseArray($sandbox->profile()->get()->response, 'sandbox profile');
    $live->zatcaPhase2()->status();
    $sandbox->zatcaPhase2()->status();
    smokeStep($step);

    $step = 'safe profile update';
    $profileOriginalFooter = is_string($liveProfile['footer_text'] ?? null) ? $liveProfile['footer_text'] : '';
    $live->profile()->update(['footer_text' => $marker]);
    $profileChanged = true;
    $updatedProfile = responseArray($live->profile()->get()->response, 'updated profile');
    smokeAssert(($updatedProfile['footer_text'] ?? null) === $marker, 'The profile update was not returned by the API.');
    smokeStep($step);

    $step = 'live client lifecycle';
    $createdClient = responseArray($live->clients()->create(new ClientCreateData(
        name: 'Laravel SDK Staging Client',
        mobileNumber: '500000000',
        countryCode: '+966',
        email: "{$marker}@example.invalid",
        address: 'Riyadh',
    ))->response, 'client creation');
    $liveClientId = responseInt($createdClient, ['user_id', 'id'], 'client creation');
    $live->clients()->find($liveClientId);
    $live->clients()->update(new ClientUpdateData(id: $liveClientId, address: 'Jeddah'));
    $live->clients()->list(new ListOptions(page: 1, perPage: 20, searchKey: 'Laravel SDK Staging'));
    $live->clients()->categories();
    smokeStep($step);

    $step = 'live document creation and downloads';
    $invoiceData = new InvoiceData(
        type: DocumentType::SimplifiedInvoice,
        lines: [new InvoiceLine($marker, 1.0, 1, vatPercentProduct: 15.0, discountPercent: 0.0)],
        clientType: 2,
        taxesIncluded: false,
        clientName: 'Laravel SDK Staging Buyer',
        clientAddress: 'Riyadh',
        referenceNumber: $marker,
        createdType: 'api',
    );
    $createdDocument = responseArray($live->documents()->create($invoiceData)->response, 'document creation');
    $documentId = responseInt($createdDocument, ['id'], 'document creation');
    $retainedDocuments++;
    $document = waitForDocument($live, $documentId);
    smokeAssert(is_string($document['invoice_number'] ?? null), 'The created document has no invoice number.');
    $live->documents()->list(new InvoiceListOptions(
        page: 1,
        perPage: 20,
        searchKey: $marker,
        includeAllTypes: true,
        types: [DocumentType::SimplifiedInvoice],
    ));
    waitForDownload(fn () => $live->documents()->downloadPdf($documentId, $temporaryDirectory.'/invoice.pdf'));
    $live->documents()->downloadXml($documentId, $temporaryDirectory.'/invoice.xml');
    smokeStep($step);

    $step = 'credit note creation';
    $credit = $live->documents()->create(new InvoiceData(
        type: DocumentType::CreditNote,
        lines: [new InvoiceLine("{$marker}-credit", 1.0, 1, vatPercentProduct: 15.0)],
        parentInvoiceNumber: $document['invoice_number'],
        documentReason: 'Controlled staging SDK verification',
        referenceNumber: "{$marker}-credit",
        createdType: 'api',
    ));
    responseInt(responseArray($credit->response, 'credit note creation'), ['id'], 'credit note creation');
    $retainedDocuments++;
    smokeStep($step);

    $step = 'product search and pagination';
    $live->products()->search($marker, limit: 20);
    $live->products()->list(new ListOptions(page: 1, perPage: 20, searchKey: $marker));
    smokeStep($step);

    $step = 'quotation lifecycle and conversion';
    $quotationPayload = [
        'document_kind' => 'quotation',
        'client_type' => 2,
        'client_name' => 'Laravel SDK Staging Buyer',
        'client_address' => 'Riyadh',
        'reference_number' => "{$marker}-quotation",
        'invoice_detail' => [
            ['description' => "{$marker}-quotation", 'unit_price' => 2, 'quantity' => 1, 'vat_percent' => 15],
        ],
    ];
    $quotation = responseArray($live->quotations()->create(new QuotationData($quotationPayload))->response, 'quotation creation');
    $quotationId = responseInt($quotation, ['id'], 'quotation creation');
    $live->quotations()->find($quotationId);
    $quotationPayload['notes'] = 'Updated by the controlled Laravel SDK staging smoke test';
    $live->quotations()->update(new QuotationData($quotationPayload, id: $quotationId));
    $live->quotations()->list(new QuotationListOptions(page: 1, perPage: 20, searchKey: $marker));
    waitForDownload(fn () => $live->quotations()->downloadPdf($quotationId, $temporaryDirectory.'/quotation.pdf'));
    $conversionData = new InvoiceData(
        type: DocumentType::SimplifiedInvoice,
        lines: [new InvoiceLine("{$marker}-converted", 2.0, 1, vatPercentProduct: 15.0)],
        clientType: 2,
        taxesIncluded: false,
        clientName: 'Laravel SDK Staging Buyer',
        clientAddress: 'Riyadh',
        referenceNumber: "{$marker}-converted",
    );
    $firstConversion = responseArray($live->quotations()->convert($quotationId, $conversionData)->response, 'quotation conversion');
    $secondConversion = responseArray($live->quotations()->convert($quotationId, $conversionData)->response, 'quotation conversion replay');
    smokeAssert(
        responseInt($firstConversion, ['id'], 'quotation conversion') === responseInt($secondConversion, ['id'], 'quotation conversion replay'),
        'Repeated quotation conversion created a second document.',
    );
    $retainedDocuments++;
    $quotationId = null;

    $quotationPayload['reference_number'] = "{$marker}-delete";
    $quotationPayload['invoice_detail'][0]['description'] = "{$marker}-delete";
    $deletionQuotation = responseArray(
        $live->quotations()->create(new QuotationData($quotationPayload))->response,
        'disposable quotation creation',
    );
    $quotationId = responseInt($deletionQuotation, ['id'], 'disposable quotation creation');
    $live->quotations()->delete($quotationId);
    $quotationId = null;
    smokeStep($step);

    $step = 'reports and archive export';
    $live->reports()->sales(new DateRange($today, $today));
    $live->reports()->vatReturn(new DateRange($today, $today));
    $live->documents()->exportArchive(
        new ArchiveRequest(ArchiveFormat::Pdf, $today, $today, [DocumentType::SimplifiedInvoice, DocumentType::CreditNote]),
        $temporaryDirectory.'/archive.zip',
    );
    smokeStep($step);

    $step = 'sandbox isolation and document workflow';
    $sandboxCreatedClient = responseArray($sandbox->clients()->create(new ClientCreateData(
        name: 'Laravel SDK Sandbox Client',
        mobileNumber: '500000001',
        countryCode: '+966',
        email: "sandbox-{$marker}@example.invalid",
        address: 'Riyadh',
    ))->response, 'sandbox client creation');
    $sandboxClientId = responseInt($sandboxCreatedClient, ['user_id', 'id'], 'sandbox client creation');
    $sandboxDocument = responseArray($sandbox->documents()->create(new InvoiceData(
        type: DocumentType::SimplifiedInvoice,
        lines: [new InvoiceLine("{$marker}-sandbox", 1.0, 1, vatPercentProduct: 15.0)],
        clientType: 2,
        taxesIncluded: false,
        clientName: 'Laravel SDK Sandbox Buyer',
        clientAddress: 'Riyadh',
        referenceNumber: "{$marker}-sandbox",
        createdType: 'api',
    ))->response, 'sandbox document creation');
    $sandboxDocumentId = responseInt($sandboxDocument, ['id'], 'sandbox document creation');
    $retainedDocuments++;
    $sandbox->documents()->find($sandboxDocumentId);
    $sandbox->documents()->list(new InvoiceListOptions(searchKey: "{$marker}-sandbox", includeAllTypes: true));
    waitForDownload(fn () => $sandbox->documents()->downloadPdf($sandboxDocumentId, $temporaryDirectory.'/sandbox-invoice.pdf'));
    smokeStep($step);

    $step = 'safe authentication error';
    $badConfiguration = $configuration;
    $badConfiguration['api_key'] = 'staging-invalid-'.bin2hex(random_bytes(16));
    $invalidKey = $badConfiguration['api_key'];
    try {
        clientFromConfiguration($badConfiguration)->profile()->get();
        throw new RuntimeException('An invalid API key was unexpectedly accepted.');
    } catch (AuthenticationException|ForbiddenException|UnauthorizedException $exception) {
        smokeAssert(! str_contains($exception->getMessage(), $invalidKey), 'An exception exposed an API key.');
    }
    smokeStep($step);
} catch (Throwable $exception) {
    fwrite(STDERR, "FAIL {$step} (".$exception::class.")\n");
    $exitCode = 1;
} finally {
    if (isset($live) && $quotationId !== null) {
        try {
            $live->quotations()->delete($quotationId);
        } catch (Throwable) {
            fwrite(STDERR, "WARN quotation cleanup requires review\n");
            $exitCode = 1;
        }
    }

    if (isset($live) && $liveClientId !== null) {
        try {
            $live->clients()->delete($liveClientId);
        } catch (Throwable) {
            fwrite(STDERR, "WARN live client cleanup requires review\n");
            $exitCode = 1;
        }
    }

    if (isset($sandbox) && $sandboxClientId !== null) {
        try {
            $sandbox->clients()->delete($sandboxClientId);
        } catch (Throwable) {
            fwrite(STDERR, "WARN sandbox client cleanup requires review\n");
            $exitCode = 1;
        }
    }

    if (isset($live) && $profileChanged) {
        try {
            $live->profile()->update(['footer_text' => $profileOriginalFooter]);
        } catch (Throwable) {
            fwrite(STDERR, "WARN profile restoration requires review\n");
            $exitCode = 1;
        }
    }

    if (is_dir($temporaryDirectory)) {
        foreach (glob($temporaryDirectory.'/*') ?: [] as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
        rmdir($temporaryDirectory);
    }
}

if ($exitCode === 0) {
    fwrite(STDOUT, "PASS cleanup of mutable staging records\n");
    fwrite(STDOUT, "PASS controlled staging smoke test ({$retainedDocuments} marked test documents retained)\n");
}

exit($exitCode);
