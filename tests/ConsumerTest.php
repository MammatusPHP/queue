<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use BBQueue\Bunny\Message;
use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\DevApp\Queue\Noop;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use Mammatus\Queue\Worker;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

use function React\Async\await;
use function str_contains;

#[TimeOut(5)]
final class ConsumerTest extends AsyncTestCase
{
    #[Test]
    public function consumeHappy(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(
            ConsumerFactory::CREATE_CONSUMER_EXPECTED,
        );
        $workerInstance                                               = Mockery::mock(WorkerContract::class);
        $workerInstance->expects('perform')->once()->with(Mockery::type(EmptyMessage::class));
        $container->expects('get')->with(Noop::class)->once()->andReturn($workerInstance);
        $logger->expects('debug')->with('Setting up logger for ' . Noop::class)->once();
        $logger->expects('debug')->with('Getting worker instance for ' . Noop::class)->once();
        $logger->expects('debug')->with('Starting 1 workers for ' . Noop::class)->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Noop::class)->atLeast()->once();
        $logger->expects('debug')->with('Hydrating message')->once();
        $logger->expects('debug')->with('Invoking worker')->once();
        $logger->expects('debug')->with('Acknowledging message')->once();

        $message = new Message();
        $message->setBody('[]');

        $worker = new Worker(
            'noop',
            1,
            Noop::class,
            'perform',
            EmptyMessage::class,
            [],
        );
        $internalConsumer->expects('receiveNoWait')->once()->andReturn($message);
        $internalConsumer->expects('acknowledge')->with($message)->once()->andReturnUsing(static function () use ($consumer): void {
            $consumer->close();
        });

        await($consumer->setupConsumer($worker));
    }

    #[Test]
    public function invalidJson(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(
            ConsumerFactory::CREATE_CONSUMER_EXPECTED,
        );
        $container->expects('get')->with(Noop::class)->once()->andReturn(Mockery::mock(WorkerContract::class));
        $logger->expects('debug')->with('Setting up logger for ' . Noop::class)->once();
        $logger->expects('debug')->with('Getting worker instance for ' . Noop::class)->once();
        $logger->expects('debug')->with('Starting 1 workers for ' . Noop::class)->once();
        $logger->expects('info')->with('Starting consumer 1 of 1 for ' . Noop::class)->once();
        $logger->expects('debug')->with('Hydrating message')->once();
        $logger->expects('debug')->with('Invoking worker')->never();
        $logger->expects('debug')->with('Rejecting message')->once();
        $logger->expects('log')->withArgs(static function (string $type, string $error): bool {
            if ($type !== 'error') {
                return false;
            }

            return str_contains($error, 'Message is not valid JSON');
        })->once();

        $message = new Message();
        $message->setBody('{]');

        $worker = new Worker(
            'noop',
            1,
            Noop::class,
            'perform',
            EmptyMessage::class,
            [],
        );
        $internalConsumer->expects('receiveNoWait')->once()->andReturn($message);
        $internalConsumer->expects('reject')->with($message)->once()->andReturnUsing(static function () use ($consumer): void {
            $consumer->close();
        });

        await($consumer->setupConsumer($worker));
    }
}
