<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Interop\Queue\Queue as InteropQueue;

final readonly class Queue implements InteropQueue
{
    public function __construct(
        private string $queueName,
    ) {
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }
}
