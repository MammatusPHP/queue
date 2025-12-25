<?php

declare(strict_types=1);

namespace Mammatus\DevApp\Queue;

use Mammatus\Kubernetes\Attributes\Resources;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

use function React\Async\await;
use function WyriHaximus\React\timedPromise;

#[Consumer(friendlyName: 'leeg', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
#[Consumer(
    friendlyName: 'vol',
    queue: 'noop',
    dtoClass: BeerMessage::class,
    concurrency: 1,
    addOns: new Resources(
        1,
        2,
    ),
)]
final readonly class Bar implements Worker
{
    public function round(BeerMessage|EmptyMessage $work): void
    {
        await(timedPromise(3, true));
    }
}
