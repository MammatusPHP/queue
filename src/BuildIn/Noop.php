<?php

declare(strict_types=1);

namespace Mammatus\Queue\BuildIn;

use Interop\Queue\Message;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

use function React\Async\await;
use function WyriHaximus\React\timedPromise;

#[Consumer(queue: 'noop', concurrency: 1)]
final class Noop implements Worker
{
    public function perform(Message $message): void
    {
        await(timedPromise(3, true));
    }
}
