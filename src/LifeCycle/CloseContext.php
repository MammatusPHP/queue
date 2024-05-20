<?php

declare(strict_types=1);

namespace Mammatus\Queue\LifeCycle;

use Interop\Queue\Context;
use Mammatus\LifeCycleEvents\Shutdown;
use WyriHaximus\Broadcast\Contracts\Listener;

final class CloseContext implements Listener
{
    public function __construct(
        private readonly Context $context,
    ) {
    }

    public function stop(Shutdown $event): void
    {
        $this->context->close();
    }
}
