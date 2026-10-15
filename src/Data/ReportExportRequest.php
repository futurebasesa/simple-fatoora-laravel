<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ReportExportRequest implements ArrayPayload
{
    public function __construct(public string $startDate, public string $endDate, public ?string $type = null, public string $lang = 'en') {}

    public function toArray(): array
    {
        return Payload::withoutNulls(['start_date' => $this->startDate, 'end_date' => $this->endDate, 'type' => $this->type, 'lang' => $this->lang]);
    }
}
