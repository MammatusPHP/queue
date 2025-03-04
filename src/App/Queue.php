<?php

declare(strict_types=1);

namespace Mammatus\Queue\App;

use Mammatus\Contracts\Argv;
use Mammatus\Queue\Contracts\Worker;

final class Queue implements Argv
{
    /** @param class-string<Worker> $className */
    public function __construct(
        public string $className,
    ) {
    }
}
