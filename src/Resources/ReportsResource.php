<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Resources;

use SimpleFatoora\Laravel\Data\ApiResponse;
use SimpleFatoora\Laravel\Data\DateRange;
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
}
