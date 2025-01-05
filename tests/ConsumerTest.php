<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use BBQueue\Bunny\Message;
use Mammatus\Queue\BuildIn\EmptyMessage;
use Mammatus\Queue\BuildIn\Noop;
use Mammatus\Queue\Worker;
use React\EventLoop\Loop;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

use function React\Async\await;
use function str_contains;

#[TimeOut(69)]
final class ConsumerTest extends AsyncTestCase
{
    /** @test */
    public function consumeHappy(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $container->expects('get')->with(Noop::class)->once()->andReturn(new Noop());
        $logger->expects('debug')->with('Setting up logger for ' . Noop::class)->once();
        $logger->expects('debug')->with('Getting worker instance for ' . Noop::class)->once();
        $logger->expects('debug')->with('Starting 1 workers for ' . Noop::class)->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->atLeast()->once();
        $logger->expects('debug')->with('Hydrating message')->once();
        $logger->expects('debug')->with('Invoking worker')->once();
        $logger->expects('debug')->with('Acknowledging message')->once();

        $message = new Message();
        $message->setBody('[]');

        $worker = new Worker(
            'internal',
            'noop',
            1,
            Noop::class,
            'perform',
            EmptyMessage::class,
            [],
        );
        $internalConsumer->expects('receiveNoWait')->andReturn($message);
        $internalConsumer->expects('acknowledge')->with($message);

        Loop::addTimer(3, static fn () => $consumer->close());
        await($consumer->setupConsumer($worker));
    }

    /** @test */
    public function invalidJson(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $container->expects('get')->with(Noop::class)->once()->andReturn(new Noop());
        $logger->expects('debug')->with('Setting up logger for ' . Noop::class)->once();
        $logger->expects('debug')->with('Getting worker instance for ' . Noop::class)->once();
        $logger->expects('debug')->with('Starting 1 workers for ' . Noop::class)->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->once();
        $logger->expects('debug')->with('Hydrating message')->atLeast()->once();
        $logger->expects('debug')->with('Invoking worker')->never();
        $logger->expects('debug')->with('Rejecting message')->atLeast()->once();
        $logger->expects('log')->withArgs(static function (string $type, string $error): bool {
            if ($type !== 'error') {
                return false;
            }

            return str_contains($error, 'Message is not valid JSON');
        })->atLeast()->once();

        $message = new Message();
        $message->setBody('{]');

        $worker = new Worker(
            'internal',
            'noop',
            1,
            Noop::class,
            'perform',
            EmptyMessage::class,
            [],
        );
        $internalConsumer->expects('receiveNoWait')->atLeast()->once()->andReturn($message);
        $internalConsumer->expects('reject')->with($message)->atLeast()->once();

        Loop::addTimer(3, static fn () => $consumer->close());
        await($consumer->setupConsumer($worker));
    }
}
