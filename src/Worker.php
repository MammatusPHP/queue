<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Mammatus\Queue\Contracts\Worker as WorkerContract;

final readonly class Worker
{
    /**
     * @param class-string<WorkerContract> $class
     * @param class-string                 $dtoClass
     * @param array<string, mixed>         $addOns
     */
    public function __construct(
        public string $type,
        public string $queue,
        public int $concurrency,
        public string $class,
        public string $method,
        public string $dtoClass,
        public array $addOns,
    ) {
    }
}
