<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Http\Client\ConnectionException as LaravelConnectionException;
use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Exceptions\ApiException;
use SimpleFatoora\Laravel\Exceptions\ForbiddenException;
use SimpleFatoora\Laravel\Exceptions\MalformedResponseException;
use SimpleFatoora\Laravel\Exceptions\NetworkException;
use SimpleFatoora\Laravel\Exceptions\RateLimitException;
use SimpleFatoora\Laravel\Exceptions\ServerException;
use SimpleFatoora\Laravel\Exceptions\TimeoutException;
use SimpleFatoora\Laravel\Exceptions\UnauthorizedException;
use SimpleFatoora\Laravel\Exceptions\ValidationException;
use SimpleFatoora\Laravel\Tests\TestCase;

final class ErrorHandlingTest extends TestCase
{
    public function test_validation_errors_are_typed(): void
    {
        Http::fake(['*' => Http::response(['status' => false, 'response' => ['Email is required']], 400)]);

        $this->expectException(ValidationException::class);
        $this->client()->account()->sendEmailOtp('');
    }

    public function test_http_422_validation_errors_are_typed(): void
    {
        Http::fake(['*' => Http::response(['status' => false, 'response' => ['Invalid field']], 422)]);

        $this->expectException(ValidationException::class);
        $this->client()->documents()->create(['invoice_type' => 0, 'invoice_detail' => []]);
    }

    public function test_unauthorized_and_forbidden_responses_are_distinct(): void
    {
        Http::fakeSequence()
            ->push(['status' => false, 'response' => 'Unauthorized'], 401)
            ->push(['status' => false, 'response' => 'Forbidden'], 403);

        try {
            $this->client()->profile()->get();
            self::fail('Expected an unauthorized exception.');
        } catch (UnauthorizedException $exception) {
            self::assertSame(401, $exception->statusCode);
        }

        $this->expectException(ForbiddenException::class);
        $this->client()->profile()->get();
    }

    public function test_rate_limit_exposes_retry_after_without_response_secrets(): void
    {
        Http::fake(['*' => Http::response(['status' => false, 'message' => 'Slow down'], 429, ['Retry-After' => '7'])]);

        try {
            $this->client()->documents()->create(['invoice_type' => 0, 'invoice_detail' => []]);
            self::fail('Expected a rate-limit exception.');
        } catch (RateLimitException $exception) {
            self::assertSame(7, $exception->retryAfterSeconds);
            self::assertSame(429, $exception->statusCode);
        }
    }

    public function test_server_errors_and_false_success_envelopes_are_typed(): void
    {
        Http::fakeSequence()
            ->push(['status' => false, 'response' => ['message' => 'Unavailable']], 500)
            ->push(['status' => false, 'message' => 'Request could not be completed'], 200);

        try {
            $this->client()->documents()->create(['invoice_type' => 0, 'invoice_detail' => []]);
            self::fail('Expected a server exception.');
        } catch (ServerException $exception) {
            self::assertSame(500, $exception->statusCode);
        }

        $this->expectException(ApiException::class);
        $this->client()->documents()->create(['invoice_type' => 0, 'invoice_detail' => []]);
    }

    public function test_malformed_json_is_rejected_without_exposing_the_body(): void
    {
        Http::fake(['*' => Http::response('<html>gateway error</html>', 200, ['Content-Type' => 'text/html'])]);

        try {
            $this->client()->profile()->get();
            self::fail('Expected a malformed-response exception.');
        } catch (MalformedResponseException $exception) {
            self::assertStringNotContainsString('gateway error', $exception->getMessage());
        }
    }

    public function test_connection_failures_are_typed_and_redacted(): void
    {
        Http::fake(static function (): never {
            throw new LaravelConnectionException('Connection failed with X-API-Key: live-secret-key');
        });

        try {
            $this->client()->profile()->get();
            self::fail('Expected a network exception.');
        } catch (NetworkException $exception) {
            self::assertStringNotContainsString('live-secret-key', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }

    public function test_timeout_failures_are_typed(): void
    {
        Http::fake(static function (): never {
            throw new LaravelConnectionException('Operation timed out; password=hidden-value');
        });

        $this->expectException(TimeoutException::class);
        $this->client()->profile()->get();
    }

    public function test_exception_details_redact_sensitive_fields(): void
    {
        Http::fake(['*' => Http::response([
            'status' => false,
            'message' => 'Invalid API key live-secret-key',
            'api_key' => 'live-secret-key',
            'password' => 'password-value',
            'context' => ['active_api_key' => 'nested-key', 'registration_intent' => 'intent-value'],
        ], 403)]);

        try {
            $this->client()->profile()->get();
            self::fail('Expected a forbidden exception.');
        } catch (ForbiddenException $exception) {
            self::assertStringNotContainsString('live-secret-key', $exception->getMessage());
            self::assertSame('[REDACTED]', $exception->details['api_key'] ?? null);
            self::assertSame('[REDACTED]', $exception->details['password'] ?? null);
            self::assertSame('[REDACTED]', $exception->details['context']['active_api_key'] ?? null);
            self::assertSame('[REDACTED]', $exception->details['context']['registration_intent'] ?? null);
        }
    }
}
