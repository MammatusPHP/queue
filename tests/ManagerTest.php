<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Mammatus\LifeCycleEvents\Boot;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\BuildIn\Noop;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use Mammatus\Queue\Manager;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Throwable;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

use function array_key_exists;
use function React\Async\await;
use function React\Promise\Timer\sleep;
use function str_contains;

use const PHP_INT_MAX;

#[TimeOut(133)]
final class ManagerTest extends AsyncTestCase
{
    #[Test]
    public function runHappy(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Noop());

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $logger->expects('debug')->withArgs(static fn (string $error): bool => str_contains($error, ' for ' . Noop::class))->atLeast()->once();
        $logger->expects('debug')->with('Starting queue manager')->once();
        $logger->expects('debug')->with('Started queue manager')->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 1 of 2 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 2 of 2 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 1 of 3 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 2 of 3 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 3 of 3 for ' . Noop::class)->atLeast()->once();
        $logger->expects('debug')->with('Stopping queue manager')->once();
        $logger->expects('debug')->with('Stopped queue manager')->once();

        $manager = new Manager(
            $consumer,
            $eventDispatcher,
            $logger,
        );
        $manager->start(new Boot());
        await(sleep(9));
        $manager->stop(new Shutdown());
    }

    #[Test]
    public function runAngry(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $exception = new RuntimeException('Ik ben boos!');

        $logger->expects('debug')->withArgs(static fn (string $error): bool => str_contains($error, ' for ' . Noop::class))->atLeast()->once();
        $logger->expects('debug')->with('Starting queue manager')->once();
        $logger->expects('debug')->with('Started queue manager')->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 1 of 2 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 2 of 2 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 1 of 3 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 2 of 3 for ' . Noop::class)->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 3 of 3 for ' . Noop::class)->atLeast()->once();
        $logger->expects('debug')->with('Stopping queue manager')->once();
        $logger->expects('debug')->with('Stopped queue manager')->once();

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Angry($exception));

        $manager = new Manager(
            $consumer,
            $eventDispatcher,
            $logger,
        );
        $manager->start(new Boot());
        await(sleep(9));
        $manager->stop(new Shutdown());
    }

    #[Test]
    public function notAnWorker(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_NOT_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Sad());

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->expects('dispatch')->withArgs(static fn (Shutdown $event): bool => true)->once();

        $logger->expects('debug')->withArgs(static fn (string $error): bool => str_contains($error, ' for ' . Noop::class))->atLeast()->once();
        $logger->expects('debug')->with('Starting queue manager')->once();
        $logger->expects('error')->withArgs(static function (string $error, array $context): bool {
            if ($error !== 'Worker errored: Worker instance must be instance of ' . WorkerContract::class) {
                return false;
            }

            return array_key_exists('exception', $context) && $context['exception'] instanceof Throwable && $context['exception']->getMessage() === 'Worker instance must be instance of ' . WorkerContract::class;
        })->atLeast()->once();
        $logger->expects('debug')->with('Started queue manager')->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Noop::class)->never();
        $logger->expects('debug')->with('Stopping queue manager')->never();
        $logger->expects('debug')->with('Stopped queue manager')->never();

        $manager = new Manager(
            $consumer,
            $eventDispatcher,
            $logger,
        );
        $manager->start(new Boot());
    }
}
