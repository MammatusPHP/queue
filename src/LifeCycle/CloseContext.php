<?php

declare(strict_types=1);

namespace Mammatus\Queue\LifeCycle;

use Interop\Queue\Context;
use Mammatus\LifeCycleEvents\Shutdown;
use WyriHaximus\Broadcast\Contracts\AsyncListener;

final class CloseContext implements AsyncListener
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
