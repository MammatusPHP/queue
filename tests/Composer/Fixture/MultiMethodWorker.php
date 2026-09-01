<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer\Fixture;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

#[Consumer(friendlyName: '', queue: 'noop', dtoClass: EmptyMessage::class, concurrency: 1)]
final class MultiMethodWorker implements Worker
{
    public function first(EmptyMessage $work): void
    {
    }

    /** @phpstan-ignore method.unused */
    private function skip(): void
    {
    }

    public function second(EmptyMessage $work): void
    {
    }
}
