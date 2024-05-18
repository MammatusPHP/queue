<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Interop\Queue\Context;
use Mammatus\LifeCycleEvents\Initialize;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use Mammatus\Queue\Generated\AbstractList_;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use Throwable;
use WyriHaximus\Broadcast\Contracts\Listener;

use function assert;
use function React\Async\async;
use function React\Async\await;
use function WyriHaximus\React\futurePromise;

final class Manager extends AbstractList_ implements Listener
{
    private bool $running = false;

    public function __construct(private LoggerInterface $logger, private Context $context, private ContainerInterface $container)
    {
    }

    public function start(Initialize $event): void
    {
        $this->logger->debug('Starting queue manager');
        $this->running = true;
        $this->boot();
        $this->logger->debug('Started queue manager');
    }

    public function stop(Shutdown $event): void
    {
        $this->logger->debug('Stopping queue manager');
        $this->running = false;
        $this->logger->debug('Stopped queue manager');
    }

    private function boot(): void
    {
        foreach ($this->workers() as $worker) {
            if ($worker->type !== 'internal') {
                continue;
            }

            for ($i = 0; $i < $worker->concurrency; $i++) {
                $this->logger->info('Starting consumer ' . $i . ' of ' . $worker->concurrency . ' for ' . $worker->class);
                Loop::futureTick(async(fn () => $this->consume($worker)));
            }
        }
    }

    private function consume(Worker $worker): void
    {
        $consumer       = $this->context->createConsumer(new Queue($worker->queue));
        $workerInstance = $this->container->get($worker->class);
        assert($workerInstance instanceof WorkerContract);
        while ($this->running) {
            $message = $consumer->receiveNoWait();
            if ($message === null) {
                await(futurePromise());
                continue;
            }

            try {
                $workerInstance->perform($message);
                $consumer->acknowledge($message);
            } catch (Throwable) {
                $consumer->reject($message);
            }
        }
    }
}
