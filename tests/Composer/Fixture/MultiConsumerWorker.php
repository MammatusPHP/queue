<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\BeerMessage;
use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

#[Consumer(friendlyName: 'beer', queue: 'noop', dtoClass: BeerMessage::class, concurrency: 1)]
#[Consumer(friendlyName: 'empty', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
final class MultiConsumerWorker implements Worker
{
    public function perform(EmptyMessage $work): void
    {
    }
}
