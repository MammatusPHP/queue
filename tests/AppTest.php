<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Mammatus\Queue\App;
use Mammatus\Queue\BuildIn\Noop;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use React\EventLoop\Loop;
use RuntimeException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function array_key_exists;

use const PHP_INT_MAX;

final class AppTest extends AsyncTestCase
{
    /** @test */
    public function runHappy(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        Loop::futureTick(static fn () => $consumer->close());

        $container->expects('get')->with(Noop::class)->once()->andReturn(new Noop());

        $context->expects('close')->once();

        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->atLeast()->once();

        $exitCode = (new App($consumer, $logger))->run(Noop::class);

        self::assertSame(0, $exitCode);
    }

    /** @test */
    public function runAngry(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        Loop::futureTick(static fn () => $consumer->close());

        $exception = new RuntimeException('Ik ben boos!');
        $container->expects('get')->with(Noop::class)->once()->andReturn(new Angry($exception));

        $context->expects('close')->once();

        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->atLeast()->once();

        $exitCode = (new App($consumer, $logger))->run(Noop::class);

        self::assertSame(0, $exitCode);
    }

    /** @test */
    public function notAnWorker(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_NOT_EXPECTED);

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Sad());

        $logger->expects('log')->withArgs(static function (string $type, string $error, array $context): bool {
            if ($type !== 'error') {
                return false;
            }

            if ($error !== 'Worker errored: Worker instance must be instance of ' . WorkerContract::class) {
                return false;
            }

            return array_key_exists('exception', $context) && $context['exception']->getMessage() === 'Worker instance must be instance of ' . WorkerContract::class;
        })->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Sad::class)->never();

        $exitCode = (new App($consumer, $logger))->run(Noop::class);

        self::assertSame(1, $exitCode);
    }
}
