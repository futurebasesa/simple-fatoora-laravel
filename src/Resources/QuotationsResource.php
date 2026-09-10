<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Data\DownloadedFile;
use SimpleFatoora\Laravel\Data\QuotationListOptions;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class QuotationsResource extends Resource
{
    /** @param array<string, mixed>|ArrayPayload $quotation */
    public function create(array|ArrayPayload $quotation): ApiResponse
    {
        return $this->transport->request(Endpoint::CreateQuotation, payload: Payload::normalize($quotation));
    }

    /** @param array<string, mixed>|ArrayPayload $quotation */
    public function update(array|ArrayPayload $quotation): ApiResponse
    {
        return $this->transport->request(Endpoint::UpdateQuotation, payload: Payload::normalize($quotation));
    }

    /** @param array<string, mixed>|QuotationListOptions $options */
    public function list(array|QuotationListOptions $options = []): ApiResponse
    {
        return $this->transport->request(Endpoint::ListQuotations, payload: Payload::normalize($options));
    }

    public function find(int $quotationId): ApiResponse
    {
        return $this->transport->request(
            Endpoint::GetQuotation,
            pathParameters: ['quotation_id' => $quotationId],
        );
    }

    public function downloadPdf(int $quotationId, string $destination, bool $overwrite = false): DownloadedFile
    {
        return $this->transport->download(
            Endpoint::DownloadQuotationPdf,
            $destination,
            pathParameters: ['quotation_id' => $quotationId],
            query: ['download' => 1],
            overwrite: $overwrite,
        );
    }

    public function delete(int $quotationId): ApiResponse
    {
        return $this->transport->request(
            Endpoint::DeleteQuotation,
            pathParameters: ['quotation_id' => $quotationId],
        );
    }

    /** @param array<string, mixed>|ArrayPayload $document */
    public function convert(int $quotationId, array|ArrayPayload $document): ApiResponse
    {
        $payload = Payload::normalize($document);
        $payload['draft_id'] = $quotationId;
        $payload['created_type'] = 'quotation';
        $payload['source_uid'] = 'quotation:'.$quotationId;

        return $this->transport->request(Endpoint::CreateInvoice, payload: $payload);
    }
}
