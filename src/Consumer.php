<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Interop\Queue as QueueInterop;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use Mammatus\Queue\Generated\AbstractList;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use Throwable;
use WyriHaximus\Broadcast\Contracts\Listener;

use function React\Async\async;
use function React\Async\await;
use function React\Promise\all;
use function WyriHaximus\React\futurePromise;

final class Consumer extends AbstractList implements Listener
{
    private bool $running = true;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly QueueInterop\Context $context,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function close(): void
    {
        $this->running = false;

        $this->context->close();
    }

    /** @return PromiseInterface<mixed> */
    public function setupConsumer(Worker $worker): PromiseInterface
    {
        $workerInstance = $this->container->get($worker->class);
        if (! ($workerInstance instanceof WorkerContract)) {
            throw new RuntimeException('Worker instance must be instance of ' . WorkerContract::class);
        }

        $promises = [];
        for ($i = 0; $i < $worker->concurrency; $i++) {
            $this->logger->info('Starting consumer ' . $i . ' of ' . $worker->concurrency . ' for ' . $worker->class);
            $promises[] = async(fn () => $this->consume($worker, $workerInstance))();
        }

        return all($promises);
    }

    private function consume(Worker $worker, WorkerContract $workerInstance): void
    {
        $consumer = $this->context->createConsumer(new Queue($worker->queue));
        while ($this->running) {
            $message = $consumer->receiveNoWait();
            if ($message === null) {
                await(futurePromise());
                continue;
            }

            try {
                $workerInstance->perform($message);
                $consumer->acknowledge($message);
            } catch (Throwable) { /** @phpstan-ignore-line */
                $consumer->reject($message);
            }
        }
    }
}
