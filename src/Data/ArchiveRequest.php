<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

use DateTimeInterface;
use InvalidArgumentException;
use SimpleFatoora\Laravel\Contracts\ArrayPayload;
use SimpleFatoora\Laravel\Enums\ArchiveFormat;
use SimpleFatoora\Laravel\Enums\DocumentType;
use SimpleFatoora\Laravel\Support\Payload;

final readonly class ArchiveRequest implements ArrayPayload
{
    /** @param list<DocumentType|int>|null $types */
    public function __construct(
        public ArchiveFormat $format,
        public DateTimeInterface|string|null $startDate = null,
        public DateTimeInterface|string|null $endDate = null,
        public ?array $types = null,
    ) {
        if (($startDate === null) !== ($endDate === null)) {
            throw new InvalidArgumentException('startDate and endDate must be supplied together.');
        }
    }

    public function toArray(): array
    {
        return Payload::withoutNulls([
            'format' => $this->format->value,
            'start_date' => self::date($this->startDate),
            'end_date' => self::date($this->endDate),
            'invoice_types' => $this->types === null ? null : array_map(
                static fn (DocumentType|int $type): int => $type instanceof DocumentType ? $type->value : $type,
                $this->types,
            ),
        ]);
    }

    private static function date(DateTimeInterface|string|null $date): ?string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;
    }
}
