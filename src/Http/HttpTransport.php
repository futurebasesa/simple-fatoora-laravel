<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Http;

use Illuminate\Http\Client\ConnectionException as LaravelConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use JsonException;
use SimpleFatoora\Laravel\Contracts\Sleeper;
use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Data\DownloadedFile;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Exceptions\ApiException;
use SimpleFatoora\Laravel\Exceptions\DownloadException;
use SimpleFatoora\Laravel\Exceptions\ForbiddenException;
use SimpleFatoora\Laravel\Exceptions\MalformedResponseException;
use SimpleFatoora\Laravel\Exceptions\NetworkException;
use SimpleFatoora\Laravel\Exceptions\RateLimitException;
use SimpleFatoora\Laravel\Exceptions\ServerException;
use SimpleFatoora\Laravel\Exceptions\SimpleFatooraException;
use SimpleFatoora\Laravel\Exceptions\TimeoutException;
use SimpleFatoora\Laravel\Exceptions\UnauthorizedException;
use SimpleFatoora\Laravel\Exceptions\ValidationException;
use SimpleFatoora\Laravel\SimpleFatooraClient;
use SimpleFatoora\Laravel\Support\Redactor;
use Throwable;

final readonly class HttpTransport
{
    public const USER_AGENT = 'simplefatoora-laravel/'.SimpleFatooraClient::VERSION;

    public function __construct(
        private Factory $http,
        private ClientConfig $config,
        private Sleeper $sleeper,
    ) {}

    public function withConfig(ClientConfig $config): self
    {
        return new self($this->http, $config, $this->sleeper);
    }

    /**
     * @param  array<string, int|string>  $pathParameters
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     */
    public function request(
        Endpoint $endpoint,
        array $pathParameters = [],
        array $query = [],
        array $payload = [],
    ): ApiResponse {
        if ($endpoint->isDownload()) {
            throw new ApiException('Download endpoints must use the streaming download method.');
        }

        $response = $this->sendWithSafeRetries(
            endpoint: $endpoint,
            url: $this->url($endpoint, $pathParameters),
            query: $query,
            payload: $payload,
        );

        return $this->parseJsonResponse($response);
    }

    public function upload(Endpoint $endpoint, string $filePath): ApiResponse
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new ValidationException('The upload file does not exist or is not readable.');
        }

        $stream = fopen($filePath, 'rb');
        if ($stream === false) {
            throw new ValidationException('The upload file could not be opened.');
        }

        try {
            $response = $this->pendingRequest($endpoint)
                ->attach('file', $stream, basename($filePath))
                ->post($this->url($endpoint));
        } catch (LaravelConnectionException $exception) {
            throw $this->networkException($exception);
        } finally {
            fclose($stream);
        }

        return $this->parseJsonResponse($response);
    }

    /**
     * @param  array<string, int|string>  $pathParameters
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     */
    public function download(
        Endpoint $endpoint,
        string $destination,
        array $pathParameters = [],
        array $query = [],
        array $payload = [],
        bool $overwrite = false,
    ): DownloadedFile {
        if (! $endpoint->isDownload()) {
            throw new DownloadException('This endpoint does not return a downloadable file.');
        }

        $this->validateDestination($destination, $overwrite);
        $directory = dirname($destination);
        $temporary = tempnam($directory, '.simplefatoora-');

        if ($temporary === false) {
            throw new DownloadException('Unable to create a temporary download file.');
        }

        try {
            $response = $this->sendWithSafeRetries(
                endpoint: $endpoint,
                url: $this->url($endpoint, $pathParameters),
                query: $query,
                payload: $payload,
                sink: $temporary,
            );

            if ((filesize($temporary) ?: 0) === 0 && $response->body() !== '') {
                file_put_contents($temporary, $response->body(), LOCK_EX);
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if ($response->failed() || str_contains($contentType, 'json')) {
                $this->throwDownloadResponse($response, $temporary);
            }

            if (! $this->isExpectedDownload($endpoint, $temporary)) {
                throw new DownloadException('Simple Fatoora returned an unexpected download format.', $response->status());
            }

            $size = filesize($temporary);
            if ($size === false || $size < 1) {
                throw new DownloadException('Simple Fatoora returned an empty download.');
            }

            if (! rename($temporary, $destination)) {
                throw new DownloadException('The downloaded file could not be moved to its destination.');
            }

            return new DownloadedFile(
                path: $destination,
                contentType: $contentType,
                filename: $this->filename($response),
                size: $size,
            );
        } catch (Throwable $exception) {
            if (is_file($temporary)) {
                unlink($temporary);
            }

            if ($exception instanceof SimpleFatooraException) {
                throw $exception;
            }

            throw new DownloadException('The download could not be completed.', previous: $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $payload
     */
    private function sendWithSafeRetries(
        Endpoint $endpoint,
        string $url,
        array $query,
        array $payload,
        ?string $sink = null,
    ): Response {
        $attempts = $endpoint->mayRetry() ? $this->config->retries + 1 : 1;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $options = [];
                if ($query !== []) {
                    $options['query'] = $query;
                }
                if ($payload !== []) {
                    $options['json'] = $payload;
                }
                if ($sink !== null) {
                    $options['sink'] = $sink;
                }

                $response = $this->pendingRequest($endpoint)->send($endpoint->method()->value, $url, $options);

                if ($attempt < $attempts && ($response->status() === 429 || $response->serverError())) {
                    $this->sleeper->sleep($this->retryDelay($response));

                    continue;
                }

                return $response;
            } catch (LaravelConnectionException $exception) {
                if ($attempt < $attempts) {
                    $this->sleeper->sleep($this->config->retryDelayMilliseconds);

                    continue;
                }

                throw $this->networkException($exception);
            }
        }

        throw new NetworkException('The Simple Fatoora request could not be completed.');
    }

    private function pendingRequest(Endpoint $endpoint): PendingRequest
    {
        $headers = [
            'Accept' => $endpoint->accept(),
            'User-Agent' => self::USER_AGENT,
        ];

        if ($endpoint->requiresAuthentication()) {
            $headers['X-API-Key'] = $this->config->apiKey();
        }

        return $this->http
            ->withHeaders($headers)
            ->timeout($this->config->timeout)
            ->connectTimeout($this->config->connectTimeout);
    }

    /** @param array<string, int|string> $pathParameters */
    private function url(Endpoint $endpoint, array $pathParameters = []): string
    {
        $path = $endpoint->path();
        foreach ($pathParameters as $name => $value) {
            $path = str_replace('{'.$name.'}', rawurlencode((string) $value), $path);
        }

        if (preg_match('/\{[^}]+\}/', $path) === 1) {
            throw new ValidationException('A required endpoint path parameter is missing.');
        }

        return $this->config->baseUrl.$path;
    }

    private function parseJsonResponse(Response $response): ApiResponse
    {
        $body = $response->body();

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MalformedResponseException(
                'Simple Fatoora returned a malformed JSON response.',
                $response->status(),
                previous: $exception,
            );
        }

        if (! is_array($payload)) {
            throw new MalformedResponseException('Simple Fatoora returned an unexpected JSON response.', $response->status());
        }

        $this->throwForError($response->status(), $payload, $response->header('Retry-After'));

        return ApiResponse::fromArray($payload);
    }

    /** @param array<string, mixed>|list<mixed> $payload */
    private function throwForError(int $status, array $payload, ?string $retryAfter): void
    {
        $details = Redactor::details($payload);
        $message = $this->errorMessage($payload);

        if ($status === 400 || $status === 422) {
            throw new ValidationException($message, $status, $details);
        }
        if ($status === 401) {
            throw new UnauthorizedException($message, $status, $details);
        }
        if ($status === 403) {
            throw new ForbiddenException($message, $status, $details);
        }
        if ($status === 429) {
            throw new RateLimitException($message, $this->retryAfterSeconds($retryAfter), $details);
        }
        if ($status >= 500) {
            throw new ServerException($message, $status, $details);
        }
        if ($status >= 400) {
            throw new ApiException($message, $status, $details);
        }

        if (($payload['status'] ?? true) === false) {
            if (preg_match('/api[ -]?key|unauthori[sz]ed|forbidden/i', $message) === 1) {
                throw new ForbiddenException($message, 403, $details);
            }

            if (isset($payload['response']) && is_array($payload['response'])) {
                throw new ValidationException($message, 400, $details);
            }

            throw new ApiException($message, $status, $details);
        }
    }

    /** @param array<string, mixed>|list<mixed> $payload */
    private function errorMessage(array $payload): string
    {
        $message = $payload['message'] ?? $payload['response'] ?? null;
        if (is_array($message)) {
            $message = $message['message'] ?? reset($message);
        }

        if (! is_scalar($message) || trim((string) $message) === '') {
            return 'Simple Fatoora could not process the request.';
        }

        return Redactor::message((string) $message, $this->config->secrets());
    }

    private function throwDownloadResponse(Response $response, string $temporary): never
    {
        $body = file_get_contents($temporary, false, null, 0, 1048576);
        if ($body !== false) {
            try {
                $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($payload)) {
                    $this->throwForError($response->status(), $payload, $response->header('Retry-After'));
                }
            } catch (JsonException) {
                // Fall through to a generic download error without exposing the response body.
            }
        }

        if ($response->status() >= 500) {
            throw new ServerException('Simple Fatoora could not prepare the download.', $response->status());
        }

        throw new DownloadException('Simple Fatoora did not return the requested file.', $response->status());
    }

    private function networkException(LaravelConnectionException $exception): NetworkException
    {
        $message = Redactor::message($exception->getMessage(), $this->config->secrets());
        $safeMessage = 'The Simple Fatoora network request failed.';

        if (preg_match('/timed?\s*out|timeout/i', $message) === 1) {
            return new TimeoutException('The Simple Fatoora request timed out.');
        }

        return new NetworkException($safeMessage);
    }

    private function retryDelay(Response $response): int
    {
        $seconds = $this->retryAfterSeconds($response->header('Retry-After'));

        $delay = $seconds === null
            ? $this->config->retryDelayMilliseconds
            : max($this->config->retryDelayMilliseconds, $seconds * 1000);

        return min($delay, $this->config->maxRetryDelayMilliseconds);
    }

    private function retryAfterSeconds(?string $header): ?int
    {
        if ($header === null || ! ctype_digit(trim($header))) {
            return null;
        }

        return (int) trim($header);
    }

    private function validateDestination(string $destination, bool $overwrite): void
    {
        $directory = dirname($destination);
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new DownloadException('The download destination directory is not writable.');
        }

        if (file_exists($destination) && ! $overwrite) {
            throw new DownloadException('The download destination already exists. Pass overwrite: true to replace it.');
        }
    }

    private function filename(Response $response): ?string
    {
        $disposition = $response->header('Content-Disposition');
        if ($disposition === null) {
            return null;
        }

        if (preg_match('/filename\*?=(?:UTF-8\'\')?["\']?([^"\';]+)["\']?/i', $disposition, $matches) !== 1) {
            return null;
        }

        return basename(rawurldecode(trim($matches[1])));
    }

    private function isExpectedDownload(Endpoint $endpoint, string $path): bool
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            return false;
        }

        try {
            $prefix = fread($stream, 512);
        } finally {
            fclose($stream);
        }

        if (! is_string($prefix)) {
            return false;
        }

        return match ($endpoint) {
            Endpoint::DownloadInvoicePdf,
            Endpoint::DownloadQuotationPdf => str_starts_with($prefix, '%PDF-'),
            Endpoint::DownloadInvoiceXml => str_starts_with(ltrim($this->withoutUtf8Bom($prefix)), '<'),
            Endpoint::ExportInvoiceArchive => str_starts_with($prefix, 'PK'),
            default => false,
        };
    }

    private function withoutUtf8Bom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }
}
