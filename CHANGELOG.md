# Changelog

All notable changes to this package are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package follows [Semantic Versioning](https://semver.org/).

## [0.2.0] - 2026-10-15

### Added

- Typed zero-VAT classification, transaction-date, address, discount and PDF preference fields.
- Optional idempotency keys for document creation and a dedicated conflict exception.
- Invoice-number lookup, Excel report downloads and invoice email sender resources.
- Archive continuation and completion metadata.

### Changed

- Invoice-line quantities accept positive fractional values with up to six decimal places.
- Updated API contract, examples and request/response contract checks.

## [0.1.0] - 2026-09-10

### Added

- Laravel 12 and 13 support across the tested PHP 8.2–8.5 compatibility range.
- Typed client resources for all 39 operations in the public Simple Fatoora V1 contract.
- Live and customer-sandbox API-key selection.
- Streamed PDF, XML, quotation PDF, and invoice archive downloads.
- Typed errors with sensitive-value redaction and bounded safe retries for GET requests.
- Laravel service-provider, configuration publishing, dependency injection, and facade support.
- Deterministic contract-drift checks, static analysis, formatting checks, and compatibility tests.
- An opt-in, protected staging smoke test for representative live and sandbox workflows.
- Distribution under the MIT License.

[0.2.0]: https://github.com/futurebasesa/simple-fatoora-laravel/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/futurebasesa/simple-fatoora-laravel/releases/tag/v0.1.0
