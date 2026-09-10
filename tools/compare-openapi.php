<?php

declare(strict_types=1);

use SimpleFatoora\Laravel\Tools\OpenApiContractComparator;

require dirname(__DIR__).'/vendor/autoload.php';

$arguments = $_SERVER['argv'] ?? [];

if (count($arguments) !== 3) {
    fwrite(STDERR, "Usage: php tools/compare-openapi.php <snapshot.json> <candidate.json>\n");
    exit(2);
}

try {
    $differences = (new OpenApiContractComparator)->compareFiles($arguments[1], $arguments[2]);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(2);
}

if ($differences === ['missing' => [], 'added' => [], 'changed' => []]) {
    fwrite(STDOUT, "OpenAPI contract matches the package snapshot.\n");
    exit(0);
}

foreach ($differences as $type => $operations) {
    if ($operations !== []) {
        fwrite(STDERR, ucfirst($type).' operations: '.implode(', ', $operations)."\n");
    }
}

exit(1);
