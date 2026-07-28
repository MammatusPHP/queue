<?php

declare(strict_types=1);

namespace Mammatus\Queue\Terraform;

use Mammatus\Terraform\Events\Variables;
use Mammatus\Terraform\Events\Variables\Registry\Entry;
use WyriHaximus\Broadcast\Contracts\Listener;

final class Queues implements Listener
{
    public function vars(Variables $variables): void
    {
        $variables->add(
            new Entry(
                'mammatus_queue_list',
                ['noop'],
            ),
        );
    }
}
