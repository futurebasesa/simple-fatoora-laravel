<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Data\ListOptions;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ProductsResource extends Resource
{
    public function search(string $searchKey = '', int $limit = 20): ApiResponse
    {
        return $this->transport->request(Endpoint::SearchProducts, query: [
            'search_key' => $searchKey,
            'limit' => $limit,
        ]);
    }

    /** @param array<string, mixed>|ListOptions $options */
    public function list(array|ListOptions $options = []): ApiResponse
    {
        return $this->transport->request(Endpoint::ListProducts, payload: Payload::normalize($options));
    }
}
