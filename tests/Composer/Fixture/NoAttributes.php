<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\Queue\Contracts\Worker;

final class NoAttributes implements Worker
{
    public function perform(): void
    {
    }
}
