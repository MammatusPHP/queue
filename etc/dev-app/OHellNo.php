<?php

declare(strict_types=1);

namespace Mammatus\DevApp\Queue;

use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

use function React\Async\await;
use function WyriHaximus\React\timedPromise;

#[Consumer(friendlyName: '', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
#[Consumer(friendlyName: 'noop_2', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 2)]
#[Consumer(friendlyName: 'noop_3', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 3)]
final readonly class OHellNo implements Worker
{
    /** @phpstan-ignore property.onlyWritten */
    public function __construct(private EmptyMessage $work)
    {
    }

    public function proost(BeerMessage $work): void
    {
        await(timedPromise(3, true));
    }

    public function cheers(BeerMessage $work, EmptyMessage $glass): void
    {
        await(timedPromise(3, true));
    }

    /** @phpstan-ignore method.unused */
    private function prut(BeerMessage $work): void
    {
        await(timedPromise(3, true));
    }
}
