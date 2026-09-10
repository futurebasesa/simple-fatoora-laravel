<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use SimpleFatoora\Laravel\Facades\SimpleFatoora;
use SimpleFatoora\Laravel\SimpleFatooraClient;
use SimpleFatoora\Laravel\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_client_is_registered_as_a_singleton_and_alias(): void
    {
        $client = $this->client();

        self::assertSame($client, $this->client());
        self::assertSame($client, $this->application()->make('simplefatoora'));
        self::assertInstanceOf(SimpleFatooraClient::class, SimpleFatoora::getFacadeRoot());
    }

    public function test_configuration_can_be_published(): void
    {
        $published = config_path('simplefatoora.php');
        if (is_file($published)) {
            unlink($published);
        }

        Artisan::call('vendor:publish', [
            '--provider' => 'SimpleFatoora\\Laravel\\SimpleFatooraServiceProvider',
            '--tag' => 'simplefatoora-config',
            '--force' => true,
        ]);

        self::assertFileExists($published);
        self::assertStringContainsString('SIMPLE_FATOORA_API_KEY', (string) file_get_contents($published));

        unlink($published);
    }
}
