<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Data\ArchiveRequest;
use SimpleFatoora\Laravel\Data\DownloadedFile;
use SimpleFatoora\Laravel\Data\InvoiceListOptions;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class DocumentsResource extends Resource
{
    /** @param array<string, mixed>|ArrayPayload $document */
    public function create(array|ArrayPayload $document): ApiResponse
    {
        return $this->transport->request(Endpoint::CreateInvoice, payload: Payload::normalize($document));
    }

    /** @param array<string, mixed>|InvoiceListOptions $options */
    public function list(array|InvoiceListOptions $options = []): ApiResponse
    {
        return $this->transport->request(Endpoint::ListInvoices, payload: Payload::normalize($options));
    }

    public function find(int $documentId): ApiResponse
    {
        return $this->transport->request(
            Endpoint::GetInvoice,
            pathParameters: ['document_id' => $documentId],
        );
    }

    public function downloadPdf(int $documentId, string $destination, bool $overwrite = false): DownloadedFile
    {
        return $this->transport->download(
            Endpoint::DownloadInvoicePdf,
            $destination,
            pathParameters: ['document_id' => $documentId],
            query: ['download' => 1],
            overwrite: $overwrite,
        );
    }

    public function downloadXml(int $documentId, string $destination, bool $overwrite = false): DownloadedFile
    {
        return $this->transport->download(
            Endpoint::DownloadInvoiceXml,
            $destination,
            pathParameters: ['document_id' => $documentId],
            overwrite: $overwrite,
        );
    }

    /** @param array<string, mixed>|ArchiveRequest $request */
    public function exportArchive(array|ArchiveRequest $request, string $destination, bool $overwrite = false): DownloadedFile
    {
        return $this->transport->download(
            Endpoint::ExportInvoiceArchive,
            $destination,
            payload: Payload::normalize($request),
            overwrite: $overwrite,
        );
    }
}
