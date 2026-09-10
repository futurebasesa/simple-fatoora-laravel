<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Contracts;

interface ArrayPayload
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
