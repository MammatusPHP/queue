<?php

declare(strict_types=1);

namespace Mammatus\Queue\LifeCycle;

use Interop\Queue\Context;
use Mammatus\LifeCycleEvents\Shutdown;
use WyriHaximus\Broadcast\Contracts\AsyncListener;

use function React\Async\await;
use function React\Promise\Timer\sleep;

final readonly class CloseContext implements AsyncListener
{
    /** @phpstan-ignore shipmonk.deadMethod */
    public function __construct(
        private Context $context,
    ) {
    }

    /** @phpstan-ignore shipmonk.deadMethod */
    public function shutdown(Shutdown $event): void
    {
        await(sleep(0.1));

        $this->context->close();
    }
}
