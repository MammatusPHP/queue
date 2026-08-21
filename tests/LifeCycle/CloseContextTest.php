<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\LifeCycle;

use Interop\Queue\Context;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\Queue\LifeCycle\CloseContext;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

#[TimeOut(5)]
final class CloseContextTest extends AsyncTestCase
{
    #[Test]
    public function shutdown(): void
    {
        $context = Mockery::mock(Context::class);
        $context->expects('close')->once();

        new CloseContext($context)->shutdown(new Shutdown());
    }
}
