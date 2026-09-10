<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel;

use SimpleFatoora\Laravel\Enums\Environment;
use SimpleFatoora\Laravel\Http\ClientConfig;
use SimpleFatoora\Laravel\Http\HttpTransport;
use SimpleFatoora\Laravel\Resources\AccountResource;
use SimpleFatoora\Laravel\Resources\ClientsResource;
use SimpleFatoora\Laravel\Resources\DocumentsResource;
use SimpleFatoora\Laravel\Resources\ProductsResource;
use SimpleFatoora\Laravel\Resources\ProfileResource;
use SimpleFatoora\Laravel\Resources\QuotationsResource;
use SimpleFatoora\Laravel\Resources\ReportsResource;
use SimpleFatoora\Laravel\Resources\ZatcaPhase2Resource;

final readonly class SimpleFatooraClient
{
    public const VERSION = '0.1.0';

    public function __construct(
        private HttpTransport $transport,
        private ClientConfig $config,
    ) {}

    public function forLive(): self
    {
        return $this->forEnvironment(Environment::Live);
    }

    public function forSandbox(): self
    {
        return $this->forEnvironment(Environment::Sandbox);
    }

    public function forEnvironment(Environment|string $environment): self
    {
        $environment = is_string($environment) ? Environment::from(strtolower($environment)) : $environment;
        $config = $this->config->forEnvironment($environment);

        return new self($this->transport->withConfig($config), $config);
    }

    public function account(): AccountResource
    {
        return new AccountResource($this->transport);
    }

    public function profile(): ProfileResource
    {
        return new ProfileResource($this->transport);
    }

    public function clients(): ClientsResource
    {
        return new ClientsResource($this->transport);
    }

    public function products(): ProductsResource
    {
        return new ProductsResource($this->transport);
    }

    public function documents(): DocumentsResource
    {
        return new DocumentsResource($this->transport);
    }

    public function quotations(): QuotationsResource
    {
        return new QuotationsResource($this->transport);
    }

    public function reports(): ReportsResource
    {
        return new ReportsResource($this->transport);
    }

    public function zatcaPhase2(): ZatcaPhase2Resource
    {
        return new ZatcaPhase2Resource($this->transport);
    }
}
