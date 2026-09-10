<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Contract;

use PHPUnit\Framework\TestCase;
use SimpleFatoora\Laravel\Tools\OpenApiContractComparator;

final class OpenApiDriftTest extends TestCase
{
    private OpenApiContractComparator $comparator;

    private string $snapshot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->comparator = new OpenApiContractComparator;
        $this->snapshot = __DIR__.'/../../resources/openapi.json';
    }

    public function test_identical_contracts_have_no_drift(): void
    {
        self::assertSame(
            ['missing' => [], 'added' => [], 'changed' => []],
            $this->comparator->compareFiles($this->snapshot, $this->snapshot),
        );
    }

    public function test_route_and_method_drift_is_detected(): void
    {
        $candidate = $this->candidate(function (array &$document): void {
            $document['paths']['/users/email_send_otp']['get'] = $document['paths']['/users/email_send_otp']['post'];
            unset($document['paths']['/users/email_send_otp']['post']);
        });

        try {
            $differences = $this->comparator->compareFiles($this->snapshot, $candidate);
            self::assertSame(['sendEmailOtp'], $differences['changed']);
        } finally {
            unlink($candidate);
        }
    }

    public function test_nested_required_field_drift_is_detected(): void
    {
        $candidate = $this->candidate(function (array &$document): void {
            $document['components']['schemas']['InvoiceLine']['required'][] = 'vat_percent_product';
        });

        try {
            $differences = $this->comparator->compareFiles($this->snapshot, $candidate);
            self::assertSame(['createInvoice'], $differences['changed']);
        } finally {
            unlink($candidate);
        }
    }

    /** @param callable(array<string, mixed>&): void $change */
    private function candidate(callable $change): string
    {
        $json = file_get_contents($this->snapshot);
        self::assertNotFalse($json);
        $document = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($document);
        $change($document);

        $path = tempnam(sys_get_temp_dir(), 'simplefatoora-contract-');
        self::assertNotFalse($path);
        file_put_contents($path, json_encode($document, JSON_THROW_ON_ERROR));

        return $path;
    }
}
