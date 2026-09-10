<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Http;

use SimpleFatoora\Laravel\Enums\Environment;
use SimpleFatoora\Laravel\Exceptions\ConfigurationException;

final readonly class ClientConfig
{
    public function __construct(
        public string $baseUrl,
        public Environment $environment,
        public ?string $apiKey,
        public ?string $sandboxApiKey,
        public float $timeout,
        public float $connectTimeout,
        public int $retries,
        public int $retryDelayMilliseconds,
        public int $maxRetryDelayMilliseconds,
    ) {
        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new ConfigurationException('SIMPLE_FATOORA_BASE_URL must be a valid URL.');
        }

        if ($timeout <= 0 || $connectTimeout <= 0) {
            throw new ConfigurationException('Simple Fatoora timeouts must be greater than zero.');
        }

        if ($retries < 0 || $retryDelayMilliseconds < 0 || $maxRetryDelayMilliseconds < 0) {
            throw new ConfigurationException('Simple Fatoora retry settings cannot be negative.');
        }

        if ($maxRetryDelayMilliseconds < $retryDelayMilliseconds) {
            throw new ConfigurationException('The maximum retry delay cannot be shorter than the base retry delay.');
        }
    }

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        $environmentValue = strtolower((string) ($config['environment'] ?? Environment::Live->value));
        $environment = Environment::tryFrom($environmentValue);

        if ($environment === null) {
            throw new ConfigurationException('SIMPLE_FATOORA_ENVIRONMENT must be live or sandbox.');
        }

        return new self(
            baseUrl: rtrim((string) ($config['base_url'] ?? ''), '/'),
            environment: $environment,
            apiKey: self::nullableString($config['api_key'] ?? null),
            sandboxApiKey: self::nullableString($config['sandbox_api_key'] ?? null),
            timeout: (float) ($config['timeout'] ?? 15),
            connectTimeout: (float) ($config['connect_timeout'] ?? 5),
            retries: (int) ($config['retries'] ?? 2),
            retryDelayMilliseconds: (int) ($config['retry_delay_ms'] ?? 250),
            maxRetryDelayMilliseconds: (int) ($config['max_retry_delay_ms'] ?? 2000),
        );
    }

    public function apiKey(): string
    {
        $key = $this->environment === Environment::Sandbox ? $this->sandboxApiKey : $this->apiKey;

        if ($key === null || $key === '') {
            throw new ConfigurationException(sprintf(
                'The Simple Fatoora %s API key is not configured.',
                $this->environment->value,
            ));
        }

        return $key;
    }

    public function forEnvironment(Environment $environment): self
    {
        return new self(
            baseUrl: $this->baseUrl,
            environment: $environment,
            apiKey: $this->apiKey,
            sandboxApiKey: $this->sandboxApiKey,
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            retries: $this->retries,
            retryDelayMilliseconds: $this->retryDelayMilliseconds,
            maxRetryDelayMilliseconds: $this->maxRetryDelayMilliseconds,
        );
    }

    /** @return list<string|null> */
    public function secrets(): array
    {
        return [$this->apiKey, $this->sandboxApiKey];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
