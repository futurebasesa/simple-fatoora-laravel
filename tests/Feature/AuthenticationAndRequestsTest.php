<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Data\ListOptions;
use SimpleFatoora\Laravel\Http\HttpTransport;
use SimpleFatoora\Laravel\Tests\TestCase;

final class AuthenticationAndRequestsTest extends TestCase
{
    public function test_authenticated_requests_send_the_live_key_and_package_user_agent(): void
    {
        $this->fakeJsonSuccess(['company_name' => 'Example']);

        $this->client()->profile()->get();

        Http::assertSent(static fn (Request $request): bool => $request->url() === 'https://api.example.test/v1/users/get_profile'
            && $request->hasHeader('X-API-Key', 'live-secret-key')
            && $request->hasHeader('User-Agent', HttpTransport::USER_AGENT)
            && $request->method() === 'GET'
        );
    }

    public function test_sandbox_client_uses_only_the_sandbox_key(): void
    {
        $this->fakeJsonSuccess();

        $this->client()->forSandbox()->clients()->categories();

        Http::assertSent(static fn (Request $request): bool => $request->hasHeader('X-API-Key', 'sandbox-secret-key')
            && ! str_contains(implode(' ', $request->header('X-API-Key')), 'live-secret-key')
        );
    }

    public function test_public_account_requests_do_not_send_a_configured_key(): void
    {
        $this->fakeJsonSuccess();

        $this->client()->account()->sendEmailOtp('developer@example.com');

        Http::assertSent(static fn (Request $request): bool => ! $request->hasHeader('X-API-Key')
            && $request->data() === ['email_id' => 'developer@example.com']
        );
    }

    public function test_pagination_and_search_are_serialized_for_post_list_requests(): void
    {
        $this->fakeJsonSuccess();

        $this->client()
            ->clients()
            ->list(new ListOptions(page: 3, perPage: 50, searchKey: 'customer'));

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'POST'
            && $request->data() === [
                'page' => 3,
                'per_page' => 50,
                'search_key' => 'customer',
            ]
        );
    }

    public function test_product_search_uses_query_parameters(): void
    {
        $this->fakeJsonSuccess();

        $this->client()->products()->search('coffee & tea', 10);

        Http::assertSent(static fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), 'search_key=coffee%20%26%20tea')
            && str_contains($request->url(), 'limit=10')
        );
    }
}
