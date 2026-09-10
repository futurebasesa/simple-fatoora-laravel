<?php

declare(strict_types=1);

return [
    'base_url' => env('SIMPLE_FATOORA_BASE_URL', 'https://api.simplefatoora.com/v1'),

    'environment' => env('SIMPLE_FATOORA_ENVIRONMENT', 'live'),

    'api_key' => env('SIMPLE_FATOORA_API_KEY'),

    'sandbox_api_key' => env('SIMPLE_FATOORA_SANDBOX_API_KEY'),

    'timeout' => (float) env('SIMPLE_FATOORA_TIMEOUT', 15),

    'connect_timeout' => (float) env('SIMPLE_FATOORA_CONNECT_TIMEOUT', 5),

    // Retries apply only to GET requests. Write requests are never retried automatically.
    'retries' => (int) env('SIMPLE_FATOORA_RETRIES', 2),

    'retry_delay_ms' => (int) env('SIMPLE_FATOORA_RETRY_DELAY_MS', 250),

    'max_retry_delay_ms' => (int) env('SIMPLE_FATOORA_MAX_RETRY_DELAY_MS', 2000),
];
