<?php

declare(strict_types=1);

namespace Mammatus\Queue\Kubernetes\Helm;

use Mammatus\Groups\Attributes\Group;
use Mammatus\Groups\Type;
use Mammatus\Kubernetes\Events\Helm\Values;
use Mammatus\Queue\Consumer\WorkerFactory\MammatusDevAppQueueBarViaRoundForNoopWithMammatusDevAppQueueBeerMessageAsVol;
use Mammatus\Queue\Consumer\WorkerFactory\MammatusDevAppQueueBarViaRoundForNoopWithMammatusDevAppQueueEmptyMessageAsLeeg;
use WyriHaximus\Broadcast\Contracts\Listener;

final class GroupAddons implements Listener
{
    public function values(Values $values): void
    {
        $values->addToGroup(new Group(Type::Normal, 'queue-worker-noop-vol'), MammatusDevAppQueueBarViaRoundForNoopWithMammatusDevAppQueueBeerMessageAsVol::create()->addOns);
        $values->addToGroup(new Group(Type::Normal, 'queue-worker-noop-leeg'), MammatusDevAppQueueBarViaRoundForNoopWithMammatusDevAppQueueEmptyMessageAsLeeg::create()->addOns);
    }
}
