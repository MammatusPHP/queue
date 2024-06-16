<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\Generated\AbstractList;
use Psr\Log\LoggerInterface;
use Throwable;
use WyriHaximus\Broadcast\Contracts\Listener;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

use function React\Async\async;
use function React\Async\await;
use function React\Promise\all;

final class App extends AbstractList implements Listener
{
    public function __construct(
        private readonly Consumer $consumer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function stop(Shutdown $event): void
    {
        $this->consumer->close();
    }

    public function run(string $className): int
    {
        return await(async(function (string $className): int {
            $logger = new ContextLogger($this->logger, ['worker' => $className]);
            try {
                $promises = [];
                foreach ($this->workers() as $worker) {
                    if ($worker->class !== $className) {
                        continue;
                    }

                    $promises[] = $this->consumer->setupConsumer($worker);
                }

                await(all($promises));

                $exitCode = 0;
            } catch (Throwable $throwable) { /** @phpstan-ignore-line */
                $logger->error('Worker errored: ' . $throwable->getMessage(), ['exception' => $throwable]);

                $exitCode = 1;
            }

            return $exitCode;
        })($className));
    }
}
