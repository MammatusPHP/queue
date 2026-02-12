<?php

declare(strict_types=1);

namespace Mammatus\Queue\PHPSan;

use Mammatus\Queue\Contracts\Worker;
use Override;
use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

final class ShipMonkDeadCode extends ReflectionBasedMemberUsageProvider
{
    #[Override]
    public function shouldMarkMethodAsUsed(ReflectionMethod $method): VirtualUsageData|null
    {
        if ($method->getDeclaringClass()->implementsInterface(Worker::class)) {
            return VirtualUsageData::withNote('Class is a Queue Worker');
        }

        return null;
    }
}
