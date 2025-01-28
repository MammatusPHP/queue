<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Mammatus\ExitCode;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\Generated\AbstractList;
use Mammatus\Run;
use Psr\Log\LoggerInterface;
use Throwable;
use WyriHaximus\Broadcast\Contracts\Listener;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

use function React\Async\await;
use function React\Promise\all;

final class App extends AbstractList implements Listener
{
    public function __construct(
        private readonly Consumer $consumer,
        private readonly Run $run,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function stop(Shutdown $event): void
    {
        $this->consumer->close();
    }

    public function run(string $className): ExitCode
    {
        return $this->run->execute(
            function (string $className): ExitCode {
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

                    $exitCode = ExitCode::Success;
                } catch (Throwable $throwable) { /** @phpstan-ignore-line */
                    $logger->error('Worker errored: ' . $throwable->getMessage(), ['exception' => $throwable]);

                    $exitCode = ExitCode::Failure;
                }

                return $exitCode;
            },
            $className,
        );
    }
}
