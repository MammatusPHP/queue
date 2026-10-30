<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

#[Consumer(friendlyName: 'friendly.name', queue: 'queue.name', dtoClass: EmptyMessage::class, concurrency: 1)]
final class SanitizedGroupNameWorker implements Worker
{
    public function perform(EmptyMessage $work): void
    {
    }
}
