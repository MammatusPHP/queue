<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue as QueueInterop;
use Interop\Queue\Queue;
use Mammatus\Queue\Consumer;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use const PHP_INT_MAX;

final class ConsumerFactory
{
    /** @return array{Consumer, Mockery\MockInterface&ContainerInterface, Mockery\MockInterface&QueueInterop\Context, Mockery\MockInterface&QueueInterop\Consumer, Mockery\MockInterface&LoggerInterface} */
    public static function create(): array
    {
        $container = Mockery::mock(ContainerInterface::class);
        $context   = Mockery::mock(QueueInterop\Context::class);
        $logger    = Mockery::mock(LoggerInterface::class);

        $consumerInternal = Mockery::mock(QueueInterop\Consumer::class);
        $consumerInternal->expects('receiveNoWait')->between(0, PHP_INT_MAX);

        $context->expects('createConsumer')->withArgs(static function (Queue $queue): bool {
            return true;
        })->between(0, PHP_INT_MAX)->andReturn($consumerInternal);

        $consumer = new Consumer($container, $context, $logger);

        return [
            $consumer,
            $container,
            $context,
            $consumerInternal,
            $logger,
        ];
    }
}
