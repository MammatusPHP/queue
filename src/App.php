<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Mammatus\Queue\Contracts\Worker;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use Mammatus\Queue\Generated\AbstractList_;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use RuntimeException;
use Throwable;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

use function React\Async\async;
use function React\Async\await;
use function React\Promise\all;
use function WyriHaximus\React\futurePromise;

final class App extends AbstractList_
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function run(string $className): int
    {
        $promises = [];
        foreach ($this->workers() as $worker) {
            if ($worker->class !== $className) {
                continue;
            }

            for ($i = 0; $i < $worker->concurrency; $i++) {
                $this->logger->info('Starting consumer ' . $i . ' of ' . $worker->concurrency . ' for ' . $worker->class);
                $promises[] = async(fn () => $this->consume($worker))();
            }
        }

        await(all($promises));

        return 0;
    }

    private function consume(\Mammatus\Queue\Worker $worker): void
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
