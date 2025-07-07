<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Mammatus\Queue\Contracts\Worker as WorkerContract;
use OpenTelemetry\API\Instrumentation\SpanAttribute;

final readonly class Worker
{
    /**
     * @param class-string<WorkerContract> $class
     * @param class-string                 $dtoClass
     * @param array<string, mixed>         $addOns
     */
    public function __construct(
        public string $hash,
        #[SpanAttribute]
        public string $friendlyName,
        #[SpanAttribute]
        public string $type,
        #[SpanAttribute]
        public string $queue,
        #[SpanAttribute]
        public int $concurrency,
        #[SpanAttribute]
        public string $class,
        #[SpanAttribute]
        public string $method,
        #[SpanAttribute]
        public string $dtoClass,
        public array $addOns,
    ) {
    }
}
