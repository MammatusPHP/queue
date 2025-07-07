<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue\Message;
use Mammatus\Queue\Contracts\Worker;
use Throwable;

final readonly class Angry implements Worker
{
    public function __construct(private Throwable $angry)
    {
    }

    public function perform(Message $work): never
    {
        throw $this->angry;
    }
}
