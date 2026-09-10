<?php

declare(strict_types=1);

namespace SimpleFatoora\Laravel\Support;

use SimpleFatoora\Laravel\Contracts\Sleeper;

final class SystemSleeper implements Sleeper
{
    public function sleep(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}
