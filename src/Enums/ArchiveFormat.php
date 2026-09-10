<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Enums;

enum ArchiveFormat: string
{
    case Pdf = 'pdf';
    case Xml = 'xml';
}
