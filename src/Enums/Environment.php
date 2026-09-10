<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Enums;

enum Environment: string
{
    case Live = 'live';
    case Sandbox = 'sandbox';
}
