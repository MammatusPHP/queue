<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Mammatus\Contracts\Argv;
use Mammatus\Contracts\Bootable;
use Mammatus\ExitCode;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\App\Queue;
use Mammatus\Queue\Generated\AbstractList;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use Throwable;
use WyriHaximus\Broadcast\Contracts\Listener;

use function React\Async\await;
use function React\Promise\all;
use function React\Promise\Timer\sleep;

/** @implements Bootable<Queue> */
final class App extends AbstractList implements Bootable, Listener
{
    /** @var array<PromiseInterface<mixed>> */
    private array $promises = [];

    public function __construct(
        private readonly Consumer $consumer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function stop(Shutdown $event): void
    {
        foreach ($this->promises as $promise) {
            $promise->cancel();
        }

        $this->consumer->close();
    }

    public function boot(Argv $argv): ExitCode
    {
        try {
            foreach ($this->workers() as $worker) {
                if ($worker->class !== $argv->className) {
                    continue;
                }

                $this->promises[] = $this->consumer->setupConsumer($worker);
            }

            await(all($this->promises));

            $exitCode = ExitCode::Success;
        } catch (Throwable $throwable) { /** @phpstan-ignore-line */
            $this->logger->error('Worker errored: ' . $throwable->getMessage(), ['exception' => $throwable]);

            $exitCode = ExitCode::Failure;
        }

        await(sleep(3));

        return $exitCode;
    }
}
