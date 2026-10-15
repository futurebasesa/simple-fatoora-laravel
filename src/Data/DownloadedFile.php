<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Data;

final readonly class DownloadedFile
{
    public function __construct(
        public string $path,
        public string $contentType,
        public ?string $filename,
        public int $size,
        public ?string $nextCursor = null,
        public int $part = 1,
        public int $exported = 0,
        public int $skipped = 0,
        public bool $complete = true,
    ) {}
}
