<?php declare(strict_types=1);

namespace Mammatus\Queue\Generated\Kubernetes\Helm;

use Mammatus\Kubernetes\Events\Helm\Values;
use WyriHaximus\Broadcast\Contracts\Listener;

final class GroupAddons implements Listener
{
    public function values(Values $values): void
    {
        $values->addToGroup(new \Mammatus\Groups\Attributes\Group(\Mammatus\Groups\Type::Normal, 'queue-worker-noop-vol'), \Mammatus\Queue\Generated\WorkerFactory\MammatusDevAppQueueBarViaRoundForNoopWithMammatusDevAppQueueBeerMessageAsVol::create()->addOns);
            }
}
