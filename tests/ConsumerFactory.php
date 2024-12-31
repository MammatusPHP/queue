<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue as QueueInterop;
use Interop\Queue\Queue;
use Mammatus\Queue\Consumer;
use Mammatus\Queue\Generated\Hydrator;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use const PHP_INT_MAX;

final class ConsumerFactory
{
    public const CREATE_CONSUMER_EXPECTED     = true;
    public const CREATE_CONSUMER_NOT_EXPECTED = false;

    /** @return array{Consumer, Mockery\MockInterface&ContainerInterface, Mockery\MockInterface&QueueInterop\Context, Mockery\MockInterface&QueueInterop\Consumer, Mockery\MockInterface&LoggerInterface} */
    public static function create(bool $createConsumerExpected): array
    {
        $container = Mockery::mock(ContainerInterface::class);
        $context   = Mockery::mock(QueueInterop\Context::class);
        $logger    = Mockery::mock(LoggerInterface::class);

        $consumerInternal = Mockery::mock(QueueInterop\Consumer::class);

        $context->expects('createConsumer')->withArgs(static function (Queue $queue): bool {
            return true;
        })->between($createConsumerExpected ? 1 : 0, PHP_INT_MAX)->andReturn($consumerInternal);

        $consumer = new Consumer($container, $context, new Hydrator(), $logger);

        return [
            $consumer,
            $container,
            $context,
            $consumerInternal,
            $logger,
        ];
    }
}
