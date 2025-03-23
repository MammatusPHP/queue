<?php

declare(strict_types=1);

namespace Mammatus\Queue\App;

use Mammatus\Contracts\Argv;

final class Queue implements Argv
{
    public function __construct(
        public string $hash,
    ) {
    }
}
