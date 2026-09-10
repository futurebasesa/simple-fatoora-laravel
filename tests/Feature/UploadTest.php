<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SimpleFatoora\Laravel\Tests\TestCase;

final class UploadTest extends TestCase
{
    public function test_profile_logo_uses_authenticated_multipart_upload(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'simplefatoora-logo-');
        self::assertNotFalse($file);
        file_put_contents($file, 'image-bytes');

        $capturedBody = null;
        Http::fake(function (Request $request) use (&$capturedBody) {
            $capturedBody = (string) $request->body();

            return Http::response([
                'status' => true,
                'response' => ['url' => 'https://cdn.example.test/logo.png'],
            ]);
        });

        try {
            $this->client()->profile()->uploadLogo($file);

            Http::assertSent(static fn (Request $request): bool => $request->method() === 'POST'
                && $request->url() === 'https://api.example.test/v1/users/upload_profile_image'
                && $request->hasHeader('X-API-Key', 'live-secret-key')
            );
            self::assertStringContainsString('image-bytes', (string) $capturedBody);
        } finally {
            unlink($file);
        }
    }
}
