# Contributing

Thank you for helping improve the official Simple Fatoora Laravel package.

## Before opening a change

Use a GitHub issue for a reproducible package defect or a focused enhancement proposal. For security concerns, follow [SECURITY.md](SECURITY.md) instead of opening a public issue. Do not include API keys, credentials, customer data, invoice contents, or private logs.

## Development setup

Requirements are PHP 8.2 or newer and Composer 2.

```bash
git clone https://github.com/futurebasesa/simple-fatoora-laravel.git
cd simple-fatoora-laravel
composer install
```

Run every local quality check before submitting a pull request:

```bash
composer validate --no-check-publish
composer test
composer analyse
composer format:check
composer audit
```

Tests must use Laravel HTTP fakes and must not call production. Add or update a deterministic test for each behavior change. Public methods and examples must remain aligned with `resources/openapi.json`.

## Pull requests

Keep changes focused, explain the user-visible behavior, and update tests and documentation together. A pull request must pass the supported PHP/Laravel matrix, static analysis, style checks, contract checks, and fresh-application compatibility checks.

By submitting a contribution, you confirm that you have the right to contribute it under the repository's license.
