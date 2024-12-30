<?php

declare(strict_types=1);

namespace Mammatus\Queue\Kubernetes\Helm;

use Mammatus\Kubernetes\Events\Helm\Values;
use Mammatus\Queue\Generated\AbstractList;
use Mammatus\Queue\Worker;
use WyriHaximus\Broadcast\Contracts\Listener;

use function array_filter;
use function array_map;
use function str_replace;

final class QueueConsumersValues extends AbstractList implements Listener
{
    /** @phpstan-ignore-next-line This makes this class test able */
    public function __construct(
        private false|string $type = 'kubernetes',
    ) {
    }

    public function values(Values $values): void
    {
        $values->registry->add(
            'deployments',
            array_map(
                static fn (Worker $worker): array => [
                    'name' => 'queue-worker-' . str_replace('.', '-', $worker->queue),
                    'command' => 'mammatus-queue',
                    'arguments' => [$worker->class],
                    'addOns' => $worker->addOns,
                ],
                $this->type === false ? [...$this->workers()] : array_filter(
                    [...$this->workers()],
                    fn (Worker $worker): bool => $worker->type === $this->type,
                ),
            ),
        );
    }
}
