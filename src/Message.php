<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message as MessageContract;

final class Message implements MessageContract
{
    use MessageTrait;
}
