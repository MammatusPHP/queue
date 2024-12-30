<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue\Message;
use Mammatus\Queue\Contracts\Worker;
use Throwable;

final class Angry implements Worker
{
    public function __construct(private readonly Throwable $angry)
    {
    }

    public function perform(Message $message): void
    {
        throw $this->angry;
    }
}
