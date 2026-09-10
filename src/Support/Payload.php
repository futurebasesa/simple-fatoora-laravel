<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Support;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;

final class Payload
{
    /**
     * @param  array<string, mixed>|ArrayPayload  $payload
     * @return array<string, mixed>
     */
    public static function normalize(array|ArrayPayload $payload): array
    {
        return $payload instanceof ArrayPayload ? $payload->toArray() : $payload;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function withoutNulls(array $values): array
    {
        return array_filter($values, static fn (mixed $value): bool => $value !== null);
    }
}
