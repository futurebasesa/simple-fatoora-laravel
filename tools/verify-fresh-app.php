<?php

declare(strict_types=1);

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Facades\SimpleFatoora;
use SimpleFatoora\Laravel\SimpleFatooraClient;

$arguments = $_SERVER['argv'] ?? [];
$applicationRoot = $arguments[1] ?? null;

if (! is_string($applicationRoot) || ! is_file($applicationRoot.'/bootstrap/app.php')) {
    throw new RuntimeException('A fresh Laravel application path is required.');
}

chdir($applicationRoot);
require $applicationRoot.'/vendor/autoload.php';

$app = require $applicationRoot.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! is_file($applicationRoot.'/config/simplefatoora.php')) {
    throw new RuntimeException('The Simple Fatoora configuration was not published.');
}

$config = $app->make(ConfigRepository::class);
$config->set('simplefatoora.base_url', 'https://api.example.test/v1');
$config->set('simplefatoora.api_key', 'fresh-app-test-key');
$app->forgetInstance(SimpleFatooraClient::class);
$app->forgetInstance('simplefatoora');

Http::fake([
    '*' => Http::response(['status' => true, 'response' => ['company_name' => 'Example']], 200),
]);

$client = $app->make(SimpleFatooraClient::class);
if (! $client instanceof SimpleFatooraClient) {
    throw new RuntimeException('SimpleFatooraClient could not be resolved from the container.');
}

$injected = $client->profile()->get();
$facade = SimpleFatoora::profile()->get();

if (! is_array($injected->response) || $injected->response['company_name'] !== 'Example') {
    throw new RuntimeException('The dependency-injection example did not return the expected response.');
}
if (! is_array($facade->response) || $facade->response['company_name'] !== 'Example') {
    throw new RuntimeException('The facade example did not return the expected response.');
}

fwrite(STDOUT, "Fresh Laravel application verification passed.\n");
