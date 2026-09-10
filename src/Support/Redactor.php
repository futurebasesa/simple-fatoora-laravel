<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Support;

final class Redactor
{
    /** @param list<string|null> $secrets */
    public static function message(string $message, array $secrets = []): string
    {
        foreach ($secrets as $secret) {
            if (is_string($secret) && $secret !== '') {
                $message = str_replace($secret, '[REDACTED]', $message);
            }
        }

        $patterns = [
            '/(X-API-Key\s*[:=]\s*)[^\s,;]+/i',
            '/(Authorization\s*[:=]\s*)(?:Bearer|Basic)\s+[^\s,;]+/i',
            '/("?(?:api_key|sandbox_api_key|password|confirm_password|otp)"?\s*[:=]\s*)"?[^"\s,}]+"?/i',
        ];

        foreach ($patterns as $pattern) {
            $message = (string) preg_replace($pattern, '$1[REDACTED]', $message);
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $details
     * @return array<string, mixed>|list<mixed>
     */
    public static function details(array $details): array
    {
        foreach ($details as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (preg_match('/api[_-]?key|password|otp|token|secret|authorization|credential|registration[_-]?intent|uuid/', $normalizedKey) === 1) {
                $details[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $details[$key] = self::details($value);
            }
        }

        return $details;
    }
}
