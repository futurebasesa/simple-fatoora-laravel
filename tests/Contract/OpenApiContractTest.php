<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Contract;

use PHPUnit\Framework\TestCase;
use SimpleFatoora\Laravel\Enums\Endpoint;

final class OpenApiContractTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $spec;

    protected function setUp(): void
    {
        parent::setUp();

        $json = file_get_contents(__DIR__.'/../../resources/openapi.json');
        self::assertNotFalse($json);
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $this->spec = $decoded;
    }

    public function test_every_documented_operation_has_an_exact_package_endpoint(): void
    {
        $documented = [];
        foreach ($this->spec['paths'] as $path => $pathItem) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (isset($pathItem[$method])) {
                    $operation = $pathItem[$method];
                    $documented[$operation['operationId']] = [strtoupper($method), $path];
                }
            }
        }

        $packaged = [];
        foreach (Endpoint::cases() as $endpoint) {
            $packaged[$endpoint->value] = [$endpoint->method()->value, $endpoint->path()];
        }

        self::assertCount(39, $documented);
        self::assertSame($documented, $packaged);
    }

    public function test_authentication_and_download_classification_match_the_contract(): void
    {
        foreach (Endpoint::cases() as $endpoint) {
            $operation = $this->operation($endpoint);
            $isPublic = array_key_exists('security', $operation) && $operation['security'] === [];
            self::assertSame(! $isPublic, $endpoint->requiresAuthentication(), $endpoint->value);

            $successContent = $this->resolve($operation['responses']['200'])['content'] ?? [];
            $mediaTypes = array_keys($successContent);
            $isDownload = (bool) array_filter(
                $mediaTypes,
                static fn (mixed $type): bool => is_string($type) && $type !== 'application/json',
            );
            self::assertSame($isDownload, $endpoint->isDownload(), $endpoint->value);
        }
    }

    public function test_internal_and_compatibility_routes_are_not_public_contract_members(): void
    {
        $serialized = json_encode($this->spec, JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('/invoice/get_dashboard', $this->spec['paths']);
        self::assertStringNotContainsString('/sdk/', $serialized);
        self::assertDoesNotMatchRegularExpression('/\/membership\b|execute_payment|payment_method/i', $serialized);
    }

    /** @return array<string, mixed> */
    private function operation(Endpoint $endpoint): array
    {
        return $this->spec['paths'][$endpoint->path()][strtolower($endpoint->method()->value)];
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function resolve(array $value): array
    {
        if (! isset($value['$ref'])) {
            return $value;
        }

        $current = $this->spec;
        foreach (explode('/', ltrim($value['$ref'], '#/')) as $segment) {
            $current = $current[$segment];
        }

        return $current;
    }
}
