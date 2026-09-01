<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;
use stdClass;

#[Consumer(friendlyName: '', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
final class UnionNonWorkFirstWorker implements Worker
{
    public function perform(stdClass|EmptyMessage $work): void
    {
    }
}
