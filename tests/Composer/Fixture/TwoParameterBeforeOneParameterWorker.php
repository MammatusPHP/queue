<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

#[Consumer(friendlyName: '', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
final class TwoParameterBeforeOneParameterWorker implements Worker
{
    public function two(EmptyMessage $first, EmptyMessage $second): void
    {
    }

    public function one(EmptyMessage $work): void
    {
    }
}
