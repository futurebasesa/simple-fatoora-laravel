<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Enums\Endpoint;

final readonly class InvoiceEmailSenderResource extends Resource
{
    public function get(): ApiResponse
    {
        return $this->transport->request(Endpoint::GetInvoiceEmailSender);
    }

    /** @param array<string, mixed> $request */
    public function update(array $request): ApiResponse
    {
        return $this->transport->request(Endpoint::UpdateInvoiceEmailSender, payload: $request);
    }
}
