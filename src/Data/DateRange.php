<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use DateTimeInterface;
use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class DateRange implements ArrayPayload
{
    public function __construct(
        public DateTimeInterface|string $startDate,
        public DateTimeInterface|string $endDate,
        public ?string $type = null,
    ) {}

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'start_date' => $this->startDate instanceof DateTimeInterface ? $this->startDate->format('Y-m-d') : $this->startDate,
            'end_date' => $this->endDate instanceof DateTimeInterface ? $this->endDate->format('Y-m-d') : $this->endDate,
            'type' => $this->type,
        ]);
    }
}
