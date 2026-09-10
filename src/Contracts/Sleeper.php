<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Contracts;

interface Sleeper
{
    public function sleep(int $milliseconds): void;
}
