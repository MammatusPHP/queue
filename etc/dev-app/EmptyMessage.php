<?php

declare(strict_types=1);

namespace Mammatus\DevApp\Queue;

use Mammatus\Queue\Contracts\Work;

/** @api */
final class EmptyMessage implements Work
{
}
