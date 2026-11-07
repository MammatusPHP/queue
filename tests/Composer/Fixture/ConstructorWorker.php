<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

#[Consumer(friendlyName: 'constructor', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
final class ConstructorWorker implements Worker
{
    public function __construct()
    {
    }

    public function perform(EmptyMessage $work): void
    {
    }
}
