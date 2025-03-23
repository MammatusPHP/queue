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
use function strlen;

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
                    'name' => 'queue-worker-' . str_replace('.', '-', $worker->queue) . '-' . (strlen($worker->friendlyName) > 0 ? $worker->friendlyName : $worker->hash),
                    'command' => 'mammatus-queue',
                    'arguments' => [$worker->hash],
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
