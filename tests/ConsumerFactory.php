<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue as QueueInterop;
use Mammatus\Queue\Consumer;
use Mammatus\Queue\Encoder\JSON;
use Mammatus\Queue\Generated\Hydrator;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use const PHP_INT_MAX;

final class ConsumerFactory
{
    public const true CREATE_CONSUMER_EXPECTED = true;
//    public const false CREATE_CONSUMER_NOT_EXPECTED = false;

    /** @return array{Consumer, Mockery\MockInterface&ContainerInterface, Mockery\MockInterface&QueueInterop\Context, Mockery\MockInterface&QueueInterop\Consumer, Mockery\MockInterface&LoggerInterface} */
    public static function create(bool $createConsumerExpected): array
    {
        $container = Mockery::mock(ContainerInterface::class);
        $context   = Mockery::mock(QueueInterop\Context::class);
        $logger    = Mockery::mock(LoggerInterface::class);

        $consumerInternal = Mockery::mock(QueueInterop\Consumer::class);

        /** @phpstan-ignore method.nonObject */
        $context->expects('createConsumer')->withArgs(static fn (): bool => true)->between($createConsumerExpected ? 1 : 0, PHP_INT_MAX)->andReturn($consumerInternal);

        $consumer = new Consumer($container, $context, new Hydrator(), new JSON(), $logger);

        return [
            $consumer,
            $container,
            $context,
            $consumerInternal,
            $logger,
        ];
    }
}
