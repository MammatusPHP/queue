<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\LifeCycle;

use Interop\Queue\Context;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\LifeCycle\CloseContext;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

use function React\Async\await;

#[TimeOut(5)]
final class CloseContextTest extends AsyncTestCase
{
    #[Test]
    public function shutdown(): void
    {
        $closeCalled                 = false;
        $closeCalledBeforeFutureTick = false;
        $context                     = Mockery::mock(Context::class);
        $context->allows('close')->once()->andReturnUsing(static function () use (&$closeCalled): void {
            $closeCalled = true;
        });
        $futureTickProcessed = new Deferred();

        Loop::futureTick(static function () use (&$closeCalled, &$closeCalledBeforeFutureTick, $futureTickProcessed): void {
            if ($closeCalled) {
                $closeCalledBeforeFutureTick = true;
            }

            $futureTickProcessed->resolve(null);
        });

        new CloseContext($context)->shutdown(new Shutdown());

        await($futureTickProcessed->promise());

        self::assertFalse($closeCalledBeforeFutureTick);
        self::assertTrue($closeCalled);
    }
}
