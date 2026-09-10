<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Data\ListOptions;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ClientsResource extends Resource
{
    public function categories(): ApiResponse
    {
        return $this->transport->request(Endpoint::ListClientCategories);
    }

    /** @param array<string, mixed>|ArrayPayload $client */
    public function create(array|ArrayPayload $client): ApiResponse
    {
        return $this->transport->request(Endpoint::CreateClient, payload: Payload::normalize($client));
    }

    /** @param array<string, mixed>|ArrayPayload $client */
    public function update(array|ArrayPayload $client): ApiResponse
    {
        return $this->transport->request(Endpoint::UpdateClient, payload: Payload::normalize($client));
    }

    public function find(int $clientId): ApiResponse
    {
        return $this->transport->request(
            Endpoint::GetClient,
            pathParameters: ['client_id' => $clientId],
        );
    }

    /** @param array<string, mixed>|ListOptions $options */
    public function list(array|ListOptions $options = []): ApiResponse
    {
        return $this->transport->request(Endpoint::ListClients, payload: Payload::normalize($options));
    }

    public function delete(int $clientId): ApiResponse
    {
        return $this->transport->request(
            Endpoint::DeleteClient,
            pathParameters: ['client_id' => $clientId],
        );
    }
}
