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
        $workerContext = Mockery::subset([
            'worker' => Noop::class,
            'method' => 'perform',
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
        ]);
        $fiberContext  = Mockery::subset([
            'worker' => Noop::class,
            'method' => 'perform',
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
            'fiber' => 0,
        ]);

        $logger->expects('log')->with('debug', 'Setting up logger for {worker}', $workerContext)->once();
        $logger->expects('log')->with('debug', 'Getting worker instance for {worker}', $workerContext)->once();
        $logger->expects('log')->with('debug', 'Starting {concurrency} workers for queue {queue} with DTO {dtoClass}', Mockery::subset([
            'concurrency' => 1,
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
        ]))->once();
        $logger->expects('log')->with('info', 'Starting consumer {index} of {concurrency} for queue {queue} with DTO {dtoClass}', Mockery::subset([
            'index' => 1,
            'concurrency' => 1,
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
        ]))->atLeast()->once();
        $logger->expects('log')->with('debug', 'Hydrating message', $fiberContext)->once();
        $logger->expects('log')->with('debug', 'Invoking worker', $fiberContext)->once();
        $logger->expects('log')->with('debug', 'Acknowledging message', $fiberContext)->once();

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
        $workerContext = Mockery::subset([
            'worker' => Noop::class,
            'method' => 'perform',
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
        ]);
        $fiberContext  = Mockery::subset([
            'worker' => Noop::class,
            'method' => 'perform',
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
            'fiber' => 0,
        ]);

        $logger->expects('log')->with('debug', 'Setting up logger for {worker}', $workerContext)->once();
        $logger->expects('log')->with('debug', 'Getting worker instance for {worker}', $workerContext)->once();
        $logger->expects('log')->with('debug', 'Starting {concurrency} workers for queue {queue} with DTO {dtoClass}', Mockery::subset([
            'concurrency' => 1,
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
        ]))->once();
        $logger->expects('log')->with('info', 'Starting consumer {index} of {concurrency} for queue {queue} with DTO {dtoClass}', Mockery::subset([
            'index' => 1,
            'concurrency' => 1,
            'queue' => 'noop',
            'dtoClass' => EmptyMessage::class,
        ]))->once();
        $logger->expects('log')->with('debug', 'Hydrating message', $fiberContext)->once();
        $logger->expects('log')->with('debug', 'Invoking worker', $fiberContext)->never();
        $logger->expects('log')->with('debug', 'Rejecting message', $fiberContext)->once();
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
