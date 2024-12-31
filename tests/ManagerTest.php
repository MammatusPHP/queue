<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Mammatus\LifeCycleEvents\Initialize;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\BuildIn\Noop;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use Mammatus\Queue\Manager;
use Mockery;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

use function array_key_exists;
use function React\Async\await;
use function React\Promise\Timer\sleep;

use const PHP_INT_MAX;

#[TimeOut(133)]
final class ManagerTest extends AsyncTestCase
{
    /** @test */
    public function runHappy(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Noop());

        $context->expects('close')->once();

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $logger->expects('debug')->with('Starting queue manager')->once();
        $logger->expects('debug')->with('Started queue manager')->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->atLeast()->once();
        $logger->expects('debug')->with('Stopping queue manager')->once();
        $logger->expects('debug')->with('Stopped queue manager')->once();

        $manager = new Manager(
            $consumer,
            $eventDispatcher,
            $logger,
        );
        $manager->start(new Initialize());
        await(sleep(9));
        $manager->stop(new Shutdown());
    }

    /** @test */
    public function runAngry(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        $context->expects('close')->once();

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $exception = new RuntimeException('Ik ben boos!');

        $logger->expects('debug')->with('Starting queue manager')->once();
        $logger->expects('debug')->with('Started queue manager')->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->atLeast()->once();
        $logger->expects('debug')->with('Stopping queue manager')->once();
        $logger->expects('debug')->with('Stopped queue manager')->once();

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Angry($exception));

        $manager = new Manager(
            $consumer,
            $eventDispatcher,
            $logger,
        );
        $manager->start(new Initialize());
        await(sleep(9));
        $manager->stop(new Shutdown());
    }

    /** @test */
    public function notAnWorker(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_NOT_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Sad());

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->expects('dispatch')->withArgs(static fn (Shutdown $event): bool => true)->once();

        $logger->expects('debug')->with('Starting queue manager')->once();

        $logger->expects('error')->withArgs(static function (string $error, array $context): bool {
            if ($error !== 'Worker errored: Worker instance must be instance of ' . WorkerContract::class) {
                return false;
            }

            return array_key_exists('exception', $context) && $context['exception']->getMessage() === 'Worker instance must be instance of ' . WorkerContract::class;
        })->atLeast()->once();
        $logger->expects('debug')->with('Started queue manager')->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->never();
        $logger->expects('debug')->with('Stopping queue manager')->never();
        $logger->expects('debug')->with('Stopped queue manager')->never();

        $manager = new Manager(
            $consumer,
            $eventDispatcher,
            $logger,
        );
        $manager->start(new Initialize());
    }
}
