<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Contracts\Sleeper;
use SimpleFatoora\Laravel\Exceptions\ServerException;
use SimpleFatoora\Laravel\Tests\TestCase;

final class RetryTest extends TestCase
{
    public function test_get_requests_retry_transient_server_failures(): void
    {
        Http::fakeSequence()
            ->push(['status' => false, 'message' => 'Temporary failure'], 503)
            ->push(['status' => true, 'response' => ['company_name' => 'Example']], 200);

        $response = $this->client()->profile()->get();

        self::assertTrue($response->successful);
        Http::assertSentCount(2);
    }

    public function test_post_write_requests_are_never_retried(): void
    {
        Http::fakeSequence()
            ->push(['status' => false, 'message' => 'Temporary failure'], 503)
            ->push(['status' => true, 'response' => ['id' => 1]], 200);

        try {
            $this->client()->documents()->create([
                'invoice_type' => 0,
                'invoice_detail' => [['description' => 'Item', 'unit_price' => 10, 'quantity' => 1]],
            ]);
            self::fail('Expected a server exception.');
        } catch (ServerException) {
            Http::assertSentCount(1);
        }
    }

    public function test_server_retry_after_is_bounded_by_configuration(): void
    {
        $sleeper = new class implements Sleeper
        {
            /** @var list<int> */
            public array $delays = [];

            public function sleep(int $milliseconds): void
            {
                $this->delays[] = $milliseconds;
            }
        };
        $this->application()->instance(Sleeper::class, $sleeper);
        Http::fakeSequence()
            ->push(['status' => false, 'message' => 'Slow down'], 429, ['Retry-After' => '3600'])
            ->push(['status' => true, 'response' => []], 200);

        $this->client()->profile()->get();

        self::assertSame([2000], $sleeper->delays);
    }
}
