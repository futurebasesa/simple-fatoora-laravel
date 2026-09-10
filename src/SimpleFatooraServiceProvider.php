<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use SimpleFatoora\Laravel\Contracts\Sleeper;
use SimpleFatoora\Laravel\Http\ClientConfig;
use SimpleFatoora\Laravel\Http\HttpTransport;
use SimpleFatoora\Laravel\Support\SystemSleeper;

final class SimpleFatooraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/simplefatoora.php', 'simplefatoora');

        $this->app->singleton(Sleeper::class, SystemSleeper::class);

        $this->app->singleton(ClientConfig::class, function (Application $app): ClientConfig {
            $configured = $app->make(ConfigRepository::class)->get('simplefatoora', []);
            $config = is_array($configured) ? $configured : [];

            return ClientConfig::fromArray($config);
        });

        $this->app->singleton(HttpTransport::class, fn (Application $app): HttpTransport => new HttpTransport(
            $app->make(Factory::class),
            $app->make(ClientConfig::class),
            $app->make(Sleeper::class),
        ));

        $this->app->singleton(SimpleFatooraClient::class, fn (Application $app): SimpleFatooraClient => new SimpleFatooraClient(
            $app->make(HttpTransport::class),
            $app->make(ClientConfig::class),
        ));

        $this->app->alias(SimpleFatooraClient::class, 'simplefatoora');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/simplefatoora.php' => config_path('simplefatoora.php'),
        ], 'simplefatoora-config');
    }
}
