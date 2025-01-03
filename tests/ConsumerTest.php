<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use BBQueue\Bunny\Message;
use Mammatus\Queue\BuildIn\EmptyMessage;
use Mammatus\Queue\BuildIn\Noop;
use Mammatus\Queue\Worker;
use React\EventLoop\Loop;
use RuntimeException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

use function React\Async\await;

#[TimeOut(13)]
final class ConsumerTest extends AsyncTestCase
{
    /** @test */
    public function consumeHappy(): void
    {
        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $container->expects('get')->with(Noop::class)->once()->andReturn(new Noop());
        $context->expects('close')->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->atLeast()->once();

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
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Message is not valid JSON');

        [$consumer, $container, $context, $internalConsumer, $logger] = ConsumerFactory::create(ConsumerFactory::CREATE_CONSUMER_EXPECTED);
        $container->expects('get')->with(Noop::class)->once()->andReturn(new Noop());
        $context->expects('close')->once();
        $logger->expects('info')->with('Starting consumer 0 of 1 for ' . Noop::class)->atLeast()->once();

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
        $internalConsumer->expects('receiveNoWait')->andReturn($message);
        $internalConsumer->expects('reject')->with($message);

        Loop::addTimer(3, static fn () => $consumer->close());
        await($consumer->setupConsumer($worker));
    }
}
