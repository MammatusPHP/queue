<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Mammatus\ExitCode;
use Mammatus\Queue\App;
use Mammatus\Queue\BuildIn\Noop;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use PHPUnit\Framework\Attributes\Test;
use React\EventLoop\Loop;
use RuntimeException;
use Throwable;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function array_key_exists;
use function str_contains;

use const PHP_INT_MAX;

final class AppTest extends AsyncTestCase
{
    #[Test]
    public function runHappy(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        Loop::futureTick(static fn () => $consumer->close());

        $container->expects('get')->with(Noop::class)->once()->andReturn(new Noop());

        $logger->expects('debug')->withArgs(static fn (string $error): bool => str_contains($error, ' for ' . Noop::class))->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Noop::class)->atLeast()->once();

        $exitCode = new App($consumer, $logger)->boot(new App\Queue('ae45abb14e21aa2ae051315fb47a7b12'));

        self::assertSame(ExitCode::Success, $exitCode);
    }

    #[Test]
    public function runAngry(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $internalConsumer->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        Loop::futureTick(static fn () => $consumer->close());

        $exception = new RuntimeException('Ik ben boos!');
        $container->expects('get')->with(Noop::class)->once()->andReturn(new Angry($exception));

        $logger->expects('debug')->withArgs(static fn (string $error): bool => str_contains($error, ' for ' . Noop::class))->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Noop::class)->atLeast()->once();

        $exitCode = new App($consumer, $logger)->boot(new App\Queue('ae45abb14e21aa2ae051315fb47a7b12'));

        self::assertSame(ExitCode::Success, $exitCode);
    }

    #[Test]
    public function notAnWorker(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_NOT_EXPECTED);

        $container->expects('get')->with(Noop::class)->atLeast()->once()->andReturn(new Sad());

        $logger->expects('debug')->withArgs(static fn (string $error): bool => str_contains($error, ' for ' . Noop::class))->atLeast()->once();
        $logger->expects('error')->withArgs(static function (string $error, array $context): bool {
            if ($error !== 'Worker errored: Worker instance must be instance of ' . WorkerContract::class) {
                return false;
            }

            return array_key_exists('exception', $context) && $context['exception'] instanceof Throwable && $context['exception']->getMessage() === 'Worker instance must be instance of ' . WorkerContract::class;
        })->atLeast()->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Sad::class)->never();

        $exitCode = new App($consumer, $logger)->boot(new App\Queue('ae45abb14e21aa2ae051315fb47a7b12'));

        self::assertSame(ExitCode::Failure, $exitCode);
    }
}
