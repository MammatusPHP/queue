<?php

declare(strict_types=1);

namespace Mammatus\DevApp\Queue;

use Mammatus\Queue\Contracts\Work;

final readonly class BeerMessage implements Work
{
    public function __construct(
        public string $name,
        public string $type,
        public int $alcoholPercentage,
    ) {
    }
}
