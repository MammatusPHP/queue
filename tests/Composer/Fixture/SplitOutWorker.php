<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Kubernetes\Attributes\SplitOut;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

#[SplitOut]
#[Consumer(friendlyName: 'split', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
final class SplitOutWorker implements Worker
{
    public function perform(EmptyMessage $work): void
    {
    }
}
