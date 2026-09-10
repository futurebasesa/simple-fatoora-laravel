<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Enums;

enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Delete = 'DELETE';
}
