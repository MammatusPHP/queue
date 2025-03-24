<?php

declare(strict_types=1);

namespace Mammatus\Queue\BuildIn;

use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

use function React\Async\await;
use function WyriHaximus\React\timedPromise;

#[Consumer(friendlyName: '', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
#[Consumer(friendlyName: 'noop_2', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 2)]
#[Consumer(friendlyName: 'noop_3', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 3)]
final class Noop implements Worker
{
    public function perform(EmptyMessage $work): void
    {
        await(timedPromise(3, true));
    }
}
