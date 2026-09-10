<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Http\HttpTransport;

abstract readonly class Resource
{
    public function __construct(protected HttpTransport $transport) {}
}
