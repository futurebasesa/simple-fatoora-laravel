# Simple Fatoora for Laravel

The official Laravel package for the [Simple Fatoora API](https://simplefatoora.com/en/integration). It provides a Laravel-native client for account onboarding, business profiles, clients, products, invoices and related documents, quotations, reports, downloads, and ZATCA Phase 2 workflows.

The package uses the public `https://api.simplefatoora.com/v1/` API. It does not expose administration or membership-payment operations, collect telemetry, or log request data.

## Requirements

| Laravel | PHP |
| --- | --- |
| 12 | 8.2–8.5 |
| 13 | 8.3–8.5 |

These are the combinations exercised by the automated test matrix.

## Installation

Install the package with Composer:

```bash
composer require simplefatoora/laravel
```

Laravel discovers the service provider and facade automatically. Publish the configuration file if you need to change timeouts or retry settings:

```bash
php artisan vendor:publish --tag=simplefatoora-config
```

## Configuration

Add your keys to the Laravel application's `.env` file:

```dotenv
SIMPLE_FATOORA_API_KEY=your_live_api_key
SIMPLE_FATOORA_SANDBOX_API_KEY=your_sandbox_api_key
SIMPLE_FATOORA_ENVIRONMENT=live
```

The available settings are:

| Environment variable | Default | Purpose |
| --- | --- | --- |
| `SIMPLE_FATOORA_API_KEY` | none | Key for live customer data |
| `SIMPLE_FATOORA_SANDBOX_API_KEY` | none | Key for isolated customer-sandbox data |
| `SIMPLE_FATOORA_ENVIRONMENT` | `live` | Default key and data environment: `live` or `sandbox` |
| `SIMPLE_FATOORA_BASE_URL` | `https://api.simplefatoora.com/v1` | API base URL |
| `SIMPLE_FATOORA_TIMEOUT` | `15` | Total request timeout in seconds |
| `SIMPLE_FATOORA_CONNECT_TIMEOUT` | `5` | Connection timeout in seconds |
| `SIMPLE_FATOORA_RETRIES` | `2` | Additional attempts for safe GET requests only |
| `SIMPLE_FATOORA_RETRY_DELAY_MS` | `250` | Base delay between safe retries |
| `SIMPLE_FATOORA_MAX_RETRY_DELAY_MS` | `2000` | Maximum delay between safe retries |

Keep API keys in environment variables or another server-side secret store. Never expose them in browser code, client-side JavaScript, public logs, or source control.

## First request

Type-hint `SimpleFatooraClient` in a controller, job, command, or service. Laravel resolves it from the container:

```php
<?php

use SimpleFatoora\Laravel\SimpleFatooraClient;

final class ShowBusinessProfile
{
    public function __invoke(SimpleFatooraClient $simpleFatoora): array
    {
        $result = $simpleFatoora->profile()->get();

        return $result->response;
    }
}
```

Every JSON method returns an `ApiResponse` with `successful`, `response`, `message`, and `raw` properties. `response` contains the API's response value.

The optional facade is also registered:

```php
use SimpleFatoora\Laravel\Facades\SimpleFatoora;

$profile = SimpleFatoora::profile()->get()->response;
```

Dependency injection is recommended for application services and tests.

## Live and sandbox keys

The configured environment selects which key is sent as `X-API-Key`. Customer sandbox is an isolated invoicing and ZATCA test area; membership and billing remain shared with the live account.

```php
$liveProfile = $simpleFatoora->forLive()->profile()->get();
$sandboxProfile = $simpleFatoora->forSandbox()->profile()->get();
```

The original client is not changed by `forLive()` or `forSandbox()`, so one injected client can safely create both scoped clients. Use only test data in sandbox.

## Account onboarding and API keys

Public registration uses an email-verification flow. Generate and retain a unique journey ID in your application, collect the registration details, and then complete these calls in order:

```php
use Illuminate\Support\Str;
use SimpleFatoora\Laravel\Data\RegistrationData;

$journeyId = (string) Str::uuid();
$registration = new RegistrationData(
    email: 'developer@example.com',
    password: $password,
    passwordConfirmation: $password,
    companyName: 'Example Company',
    acceptedTerms: true,
    firstName: 'Example',
    lastName: 'Developer',
);

$intent = $simpleFatoora->account()
    ->createRegistrationIntent($registration, $journeyId)
    ->response;
$registrationIntent = $intent['registration_intent'];

$simpleFatoora->account()->sendEmailOtp('developer@example.com');
$verified = $simpleFatoora->account()->verifyEmailOtp('developer@example.com', $emailOtp)->response;
$account = $simpleFatoora->account()->completeRegistration($registrationIntent)->response;
```

Use the `registration_intent` value returned by the flow; do not log it. Existing account owners can retrieve their API keys with credentials and validate a key:

```php
$keyResult = $simpleFatoora->account()
    ->retrieveApiKeys('developer@example.com', $password);
$keys = $keyResult->raw;

$validation = $simpleFatoora->account()->validateApiKey($candidateKey)->raw;

$simpleFatoora->account()->resetPassword($resetUuid, $newPassword, $newPassword);
```

Do not log credentials or key-retrieval responses.

## Business profile

```php
use SimpleFatoora\Laravel\Data\ProfileUpdateData;

$profile = $simpleFatoora->profile()->get()->response;

$updated = $simpleFatoora->profile()->update(new ProfileUpdateData(
    companyName: 'Example Company',
    address: 'Riyadh',
    companyVatNumber: '300000000000003',
))->response;

$logo = $simpleFatoora->profile()->uploadLogo(storage_path('app/company-logo.png'));
```

Logo uploads are streamed from a readable local file.

## Clients

```php
use SimpleFatoora\Laravel\Data\ClientCreateData;
use SimpleFatoora\Laravel\Data\ClientUpdateData;
use SimpleFatoora\Laravel\Data\ListOptions;

$categories = $simpleFatoora->clients()->categories()->response;

$created = $simpleFatoora->clients()->create(new ClientCreateData(
    name: 'Example Customer',
    mobileNumber: '500000000',
    countryCode: '+966',
    email: 'customer@example.com',
    address: 'Riyadh',
))->response;

$simpleFatoora->clients()->update(new ClientUpdateData(
    id: $clientId,
    address: 'Jeddah',
));

$client = $simpleFatoora->clients()->find($clientId)->response;
$clients = $simpleFatoora->clients()->list(new ListOptions(page: 1, perPage: 20, searchKey: 'Example'))->response;
$simpleFatoora->clients()->delete($clientId);
```

## Products

Products are remembered from successfully created documents. The public API supports searching and listing them:

```php
use SimpleFatoora\Laravel\Data\ListOptions;

$matches = $simpleFatoora->products()->search('Consulting', limit: 20)->response;
$products = $simpleFatoora->products()->list(new ListOptions(limit: 20))->response;
```

## Invoices and related documents

Use the typed request objects for common fields, or pass an array when the public API adds a supported field that is not represented by a data object.

```php
use SimpleFatoora\Laravel\Data\InvoiceData;
use SimpleFatoora\Laravel\Data\InvoiceLine;
use SimpleFatoora\Laravel\Enums\DocumentType;

$document = new InvoiceData(
    type: DocumentType::SimplifiedInvoice,
    clientType: 2,
    clientName: 'Example Customer',
    clientAddress: 'Riyadh',
    clientEmail: 'customer@example.com',
    referenceNumber: 'ORDER-1001',
    lines: [
        new InvoiceLine(
            description: 'Consulting',
            unitPrice: 100,
            quantity: 1,
            vatPercentProduct: 15,
            discountPercent: 0,
        ),
    ],
);

$created = $simpleFatoora->documents()->create($document)->response;
$documentId = $created['id'];
$saved = $simpleFatoora->documents()->find($documentId)->response;
```

`DocumentType` provides the supported document kinds:

```php
DocumentType::SimplifiedInvoice; // 0
DocumentType::StandardInvoice;   // 1
DocumentType::PurchaseInvoice;   // 2
DocumentType::CreditNote;        // 3
DocumentType::DebitNote;         // 4
```

Credit and debit notes use the same `create()` method and must reference an original document from the same API-key environment:

```php
$creditNote = $simpleFatoora->documents()->create(new InvoiceData(
    type: DocumentType::CreditNote,
    parentInvoiceNumber: $saved['invoice_number'],
    documentReason: 'Returned items',
    lines: [
        new InvoiceLine('Returned item', 100, 1, vatPercentProduct: 15),
    ],
))->response;
```

Use `DocumentType::DebitNote` for a debit note. The API validates the parent ownership, environment, document relationship, legal rules, and ZATCA requirements.

List and filter documents with `InvoiceListOptions`:

```php
use SimpleFatoora\Laravel\Data\InvoiceListOptions;

$documents = $simpleFatoora->documents()->list(new InvoiceListOptions(
    page: 1,
    perPage: 50,
    searchKey: 'ORDER-1001',
    includeAllTypes: true,
    types: [DocumentType::SimplifiedInvoice, DocumentType::CreditNote],
))->response;
```

## PDF, XML, and archive downloads

Downloads are streamed into a temporary file in the destination directory and moved into place only after a successful response and format check. Existing files are not overwritten unless `overwrite: true` is explicit.

```php
$pdf = $simpleFatoora->documents()->downloadPdf(
    $documentId,
    storage_path("app/invoices/{$documentId}.pdf"),
);

$xml = $simpleFatoora->documents()->downloadXml(
    $documentId,
    storage_path("app/invoices/{$documentId}.xml"),
);

use SimpleFatoora\Laravel\Data\ArchiveRequest;
use SimpleFatoora\Laravel\Enums\ArchiveFormat;

$archive = $simpleFatoora->documents()->exportArchive(
    new ArchiveRequest(
        format: ArchiveFormat::Xml,
        startDate: '2026-01-01',
        endDate: '2026-01-31',
        types: [DocumentType::SimplifiedInvoice, DocumentType::StandardInvoice],
    ),
    storage_path('app/invoices/january-2026.zip'),
);
```

Each result is a `DownloadedFile` containing `path`, `contentType`, `filename`, and `size`. XML is available only for eligible Phase 2 tax documents. Archive limits and eligibility rules are described in the [API reference](https://simplefatoora.com/en/integration).

## Quotations and conversion

```php
use SimpleFatoora\Laravel\Data\QuotationData;
use SimpleFatoora\Laravel\Data\QuotationListOptions;

$quotationPayload = [
    'document_kind' => 'quotation',
    'client_type' => 2,
    'client_name' => 'Example Customer',
    'client_address' => 'Riyadh',
    'invoice_detail' => [
        ['description' => 'Consulting', 'unit_price' => 100, 'quantity' => 1, 'vat_percent' => 15],
    ],
];

$createdQuotation = $simpleFatoora->quotations()
    ->create(new QuotationData($quotationPayload))
    ->response;

$quotationId = $createdQuotation['id'];
$quotation = $simpleFatoora->quotations()->find($quotationId)->response;
$quotations = $simpleFatoora->quotations()->list(new QuotationListOptions(page: 1, perPage: 20))->response;

$simpleFatoora->quotations()->update(new QuotationData(
    quotation: $quotationPayload,
    id: $quotationId,
));

$simpleFatoora->quotations()->downloadPdf(
    $quotationId,
    storage_path("app/quotations/{$quotationId}.pdf"),
);

$invoice = $simpleFatoora->quotations()->convert($quotationId, $document)->response;
$simpleFatoora->quotations()->delete($quotationId);
```

Conversion issues a normal document and marks the quotation converted. Repeating conversion with the same quotation ID returns the original document rather than creating a duplicate. The package still does not retry the conversion request automatically.

## Reports

```php
use SimpleFatoora\Laravel\Data\DateRange;

$sales = $simpleFatoora->reports()
    ->sales(new DateRange('2026-01-01', '2026-01-31'))
    ->response;

$vatReturn = $simpleFatoora->reports()
    ->vatReturn(new DateRange('2026-01-01', '2026-03-31'))
    ->response;
```

## ZATCA Phase 2

Read the current state before beginning or continuing onboarding:

```php
$status = $simpleFatoora->zatcaPhase2()->status()->response;
```

The following methods cover the public onboarding, recovery, refresh, and renewal contract:

```php
use SimpleFatoora\Laravel\Data\ZatcaBusinessData;

$business = new ZatcaBusinessData(
    companyName: 'Example Company',
    organizationName: 'Example Company',
    organizationIdentifier: '300000000000003',
    organizationUnitName: 'Riyadh Branch',
    companyRegistrationNumber: '1010000000',
    companyVatNumber: '300000000000003',
    industryBusinessCategory: 'Retail',
    streetName: 'Example Street',
    buildingNumber: '1234',
    district: 'Example District',
    city: 'Riyadh',
    postalCode: '12345',
    countryName: 'SA',
);

$simpleFatoora->zatcaPhase2()->saveDraft($business);
$simpleFatoora->zatcaPhase2()->generateCsr($business);
$simpleFatoora->zatcaPhase2()->submitOtp($zatcaOtp);
$simpleFatoora->zatcaPhase2()->retryCompliance();
$simpleFatoora->zatcaPhase2()->refreshStatus();
$simpleFatoora->zatcaPhase2()->renew();
```

Only an authorized business representative should start or renew ZATCA onboarding. OTPs for this flow come from ZATCA and are not the account-registration email OTP.

## Error handling

All package exceptions extend `SimpleFatooraException`:

```php
use SimpleFatoora\Laravel\Exceptions\AuthenticationException;
use SimpleFatoora\Laravel\Exceptions\NetworkException;
use SimpleFatoora\Laravel\Exceptions\RateLimitException;
use SimpleFatoora\Laravel\Exceptions\ServerException;
use SimpleFatoora\Laravel\Exceptions\SimpleFatooraException;
use SimpleFatoora\Laravel\Exceptions\ValidationException;

try {
    $result = $simpleFatoora->documents()->create($document);
} catch (ValidationException $exception) {
    report($exception);
} catch (AuthenticationException $exception) {
    report($exception);
} catch (RateLimitException $exception) {
    $retryAfter = $exception->retryAfterSeconds;
} catch (NetworkException|ServerException $exception) {
    report($exception);
} catch (SimpleFatooraException $exception) {
    report($exception);
}
```

Specific exception classes cover invalid configuration, unauthorized and forbidden responses, validation failures, rate limits, timeouts, other network failures, server errors, malformed JSON, and failed downloads. API keys, credentials, OTPs, and token-like response fields are redacted from exception messages and details.

## Timeouts and retries

Timeouts are configurable and finite. Automatic retries apply only to GET requests after connection failures, rate limits, or server errors. Retry delays are capped by `SIMPLE_FATOORA_MAX_RETRY_DELAY_MS`.

POST, PUT, PATCH, and DELETE requests are never retried automatically. This includes account creation, profile changes, client writes, document creation, quotation writes and conversion, archive generation, reports, and ZATCA operations. Your application may make a deliberate retry only after determining whether the first write succeeded.

## Testing

The package uses Laravel HTTP fakes and Orchestra Testbench, so the normal suite does not call a live service:

```bash
composer install
composer test
composer analyse
composer format:check
composer contract
```

The bundled OpenAPI snapshot is compared with every exposed endpoint. A separate contract command can compare a newly downloaded official document without making ordinary pull-request tests depend on API availability:

```bash
php tools/compare-openapi.php resources/openapi.json /path/to/current-openapi.json
```

## Versioning and upgrades

The package follows [Semantic Versioning](https://semver.org/). Releases before 1.0 may introduce interface changes in a new minor version. Patch versions preserve the documented public interface unless a security or legal correction requires otherwise. Review [CHANGELOG.md](CHANGELOG.md) before upgrading.

## Security and support

Do not post API keys, credentials, invoice contents, or customer data in a public issue. Follow [SECURITY.md](SECURITY.md) for private vulnerability reporting.

- [Simple Fatoora integration and API documentation](https://simplefatoora.com/en/integration)
- [OpenAPI document](https://simplefatoora.com/developers/openapi.json)
- [Simple Fatoora contact page](https://simplefatoora.com/en/contact-us)
- [GitHub issues](https://github.com/futurebasesa/simple-fatoora-laravel/issues)
