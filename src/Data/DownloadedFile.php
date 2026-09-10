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
    ) {}
}
