<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Data\DateRange;
use SimpleFatoora\Laravel\Data\DownloadedFile;
use SimpleFatoora\Laravel\Data\ReportExportRequest;
use SimpleFatoora\Laravel\Enums\Endpoint;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ReportsResource extends Resource
{
    /** @param array<string, mixed>|DateRange $range */
    public function sales(array|DateRange $range): ApiResponse
    {
        return $this->transport->request(Endpoint::GetSalesReport, payload: Payload::normalize($range));
    }

    /** @param array<string, mixed>|DateRange $range */
    public function vatReturn(array|DateRange $range): ApiResponse
    {
        return $this->transport->request(Endpoint::GetVatReturnReport, payload: Payload::normalize($range));
    }

    /** @param array<string, mixed>|ReportExportRequest $request */
    public function downloadDocumentsExcel(array|ReportExportRequest $request, string $destination, bool $overwrite = false): DownloadedFile
    {
        return $this->transport->download(Endpoint::ExportDocumentsExcel, $destination, payload: Payload::normalize($request), overwrite: $overwrite);
    }

    /** @param array<string, mixed>|ReportExportRequest $request */
    public function downloadVatExcel(array|ReportExportRequest $request, string $destination, bool $overwrite = false): DownloadedFile
    {
        return $this->transport->download(Endpoint::ExportVatExcel, $destination, payload: Payload::normalize($request), overwrite: $overwrite);
    }
}
