<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SimpleFatoora\Laravel\Enums\Environment;
use SimpleFatoora\Laravel\Exceptions\ConfigurationException;
use SimpleFatoora\Laravel\Http\ClientConfig;

final class ClientConfigTest extends TestCase
{
    public function test_live_and_sandbox_keys_are_selected_explicitly(): void
    {
        $config = $this->config();

        self::assertSame('live-key', $config->apiKey());
        self::assertSame('sandbox-key', $config->forEnvironment(Environment::Sandbox)->apiKey());
    }

    public function test_invalid_environment_is_rejected(): void
    {
        $this->expectException(ConfigurationException::class);
        ClientConfig::fromArray(['base_url' => 'https://example.test/v1', 'environment' => 'test']);
    }

    public function test_missing_selected_key_is_rejected_only_for_authenticated_requests(): void
    {
        $config = ClientConfig::fromArray([
            'base_url' => 'https://example.test/v1',
            'environment' => 'sandbox',
        ]);

        $this->expectException(ConfigurationException::class);
        $config->apiKey();
    }

    public function test_maximum_retry_delay_cannot_be_shorter_than_base_delay(): void
    {
        $this->expectException(ConfigurationException::class);
        ClientConfig::fromArray([
            'base_url' => 'https://example.test/v1',
            'retry_delay_ms' => 1000,
            'max_retry_delay_ms' => 500,
        ]);
    }

    private function config(): ClientConfig
    {
        return ClientConfig::fromArray([
            'base_url' => 'https://example.test/v1/',
            'environment' => 'live',
            'api_key' => 'live-key',
            'sandbox_api_key' => 'sandbox-key',
        ]);
    }
}
