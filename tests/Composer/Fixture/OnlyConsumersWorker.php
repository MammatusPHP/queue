<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Attributes\Consumers;
use Mammatus\Queue\Contracts\Worker;

#[Consumers(
    new Consumer(friendlyName: 'only', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1),
)]
final class OnlyConsumersWorker implements Worker
{
    public function perform(EmptyMessage $work): void
    {
    }
}
