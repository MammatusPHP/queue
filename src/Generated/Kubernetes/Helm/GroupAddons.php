<?php

declare(strict_types=1);

namespace Mammatus\Queue\Generated\Kubernetes\Helm;

use Mammatus\Groups\Attributes\Group;
use Mammatus\Groups\Type;
use Mammatus\Kubernetes\Events\Helm\Values;
use Mammatus\Queue\Generated\WorkerFactory\MammatusDevAppQueueBarViaRoundForNoopWithMammatusDevAppQueueBeerMessageAsVol;
use WyriHaximus\Broadcast\Contracts\Listener;

final class GroupAddons implements Listener
{
    public function values(Values $values): void
    {
        $values->addToGroup(new Group(Type::Normal, 'queue-worker-noop-vol'), MammatusDevAppQueueBarViaRoundForNoopWithMammatusDevAppQueueBeerMessageAsVol::create()->addOns);
    }
}
