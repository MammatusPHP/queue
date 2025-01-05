<?php

declare(strict_types=1);

namespace Mammatus\Queue\LifeCycle;

use Interop\Queue\Context;
use Mammatus\LifeCycleEvents\Shutdown;
use WyriHaximus\Broadcast\Contracts\AsyncListener;

use function React\Async\await;
use function React\Promise\Timer\sleep;

final class CloseContext implements AsyncListener
{
    public function __construct(
        private readonly Context $context,
    ) {
    }

    public function shutdown(Shutdown $event): void
    {
        await(sleep(0.1));

        $this->context->close();
    }
}
