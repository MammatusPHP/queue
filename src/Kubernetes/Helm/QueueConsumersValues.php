<?php

declare(strict_types=1);

namespace Mammatus\Queue\Kubernetes\Helm;

use Mammatus\Kubernetes\Events\Helm\Values;
use Mammatus\Queue\Generated\AbstractList_;
use Mammatus\Queue\Worker;
use WyriHaximus\Broadcast\Contracts\Listener;

use function array_filter;

final class QueueConsumersValues extends AbstractList_ implements Listener
{
    public function values(Values $values): void
    {
        $values->registry->add(
            'deployments',
            array_map(
                static fn (Worker $worker): array => [
                    'name' => 'queue-worker-' . str_replace('.', '-', $worker->queue),
                    'command' => 'mammatus-queue',
                    'arguments' => [$worker->class],
                ],
                array_filter(
                    [...$this->workers()],
                    static fn (Worker $worker): bool => $worker->type === 'kubernetes',
                ),
            ),
        );
    }
}
