<?php

declare(strict_types=1);

namespace Mammatus\Queue\Generated\Kubernetes\Helm;

use Mammatus\Groups\Attributes\Group;
use Mammatus\Groups\Type;
use Mammatus\Kubernetes\Events\Helm\Values;
use WyriHaximus\Broadcast\Contracts\Listener;

use function json_decode;

final class GroupAddons implements Listener
{
    public function values(Values $values): void
    {
        $values->addToGroup(new Group(Type::Normal, 'queue-worker-noop-vol'), json_decode('{"addOns":{"type":"container","helper":"mammatus.container.resources","arguments":{"cpu":"1000m","memory":"2048Mi"}}}', true));
    }
}
