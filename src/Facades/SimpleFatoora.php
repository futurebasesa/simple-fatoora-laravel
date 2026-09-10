<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SimpleFatoora\Laravel\Enums\Environment;
use SimpleFatoora\Laravel\Resources\AccountResource;
use SimpleFatoora\Laravel\Resources\ClientsResource;
use SimpleFatoora\Laravel\Resources\DocumentsResource;
use SimpleFatoora\Laravel\Resources\ProductsResource;
use SimpleFatoora\Laravel\Resources\ProfileResource;
use SimpleFatoora\Laravel\Resources\QuotationsResource;
use SimpleFatoora\Laravel\Resources\ReportsResource;
use SimpleFatoora\Laravel\Resources\ZatcaPhase2Resource;
use SimpleFatoora\Laravel\SimpleFatooraClient;

/**
 * @method static SimpleFatooraClient forLive()
 * @method static SimpleFatooraClient forSandbox()
 * @method static SimpleFatooraClient forEnvironment(Environment|string $environment)
 * @method static AccountResource account()
 * @method static ProfileResource profile()
 * @method static ClientsResource clients()
 * @method static ProductsResource products()
 * @method static DocumentsResource documents()
 * @method static QuotationsResource quotations()
 * @method static ReportsResource reports()
 * @method static ZatcaPhase2Resource zatcaPhase2()
 *
 * @see SimpleFatooraClient
 */
final class SimpleFatoora extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'simplefatoora';
    }
}
