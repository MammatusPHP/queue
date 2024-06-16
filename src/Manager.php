<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Mammatus\LifeCycleEvents\Initialize;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\Generated\AbstractList;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use WyriHaximus\Broadcast\Contracts\Listener;

final class Manager extends AbstractList implements Listener
{
    public function __construct(
        private readonly Consumer $consumer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function start(Initialize $event): void
    {
        $this->logger->debug('Starting queue manager');
        $this->boot();
        $this->logger->debug('Started queue manager');
    }

    public function stop(Shutdown $event): void
    {
        $this->logger->debug('Stopping queue manager');
        $this->consumer->close();
        $this->logger->debug('Stopped queue manager');
    }

    private function boot(): void
    {
        try {
            foreach ($this->workers() as $worker) {
                if ($worker->type !== 'internal') {
                    continue;
                }

                $this->consumer->setupConsumer($worker);
            }
        } catch (Throwable $throwable) { /** @phpstan-ignore-line */
            $this->logger->error('Worker errored: ' . $throwable->getMessage(), ['exception' => $throwable]);
            $this->eventDispatcher->dispatch(new Shutdown());
        }
    }
}
