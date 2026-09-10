<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use SimpleFatoora\Laravel\SimpleFatooraClient;
use SimpleFatoora\Laravel\SimpleFatooraServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @param Application $app */
    protected function getPackageProviders($app): array
    {
        return [SimpleFatooraServiceProvider::class];
    }

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        $app->make(ConfigRepository::class)->set('simplefatoora', [
            'base_url' => 'https://api.example.test/v1',
            'environment' => 'live',
            'api_key' => 'live-secret-key',
            'sandbox_api_key' => 'sandbox-secret-key',
            'timeout' => 15,
            'connect_timeout' => 5,
            'retries' => 2,
            'retry_delay_ms' => 0,
            'max_retry_delay_ms' => 2000,
        ]);
    }

    protected function fakeJsonSuccess(mixed $response = []): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => true,
                'response' => $response,
            ], 200, ['Content-Type' => 'application/json']),
        ]);
    }

    protected function client(): SimpleFatooraClient
    {
        return $this->application()->make(SimpleFatooraClient::class);
    }

    protected function application(): Application
    {
        self::assertNotNull($this->app);

        return $this->app;
    }
}
