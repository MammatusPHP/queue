<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Kubernetes\Helm;

use Mammatus\Kubernetes\Events\Helm\Values;
use Mammatus\Queue\Kubernetes\Helm\QueueConsumersValues;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

final class QueueConsumersValuesTest extends TestCase
{
    #[Test]
    public function none(): void
    {
        $values = new Values(new Values\Registry());
        (new QueueConsumersValues())->values($values);

        self::assertSame([
            'deployments' => [], // Empty array here because we don't have any default deployments running in Kubernetes out of the box
        ], $values->registry->get());
    }

    #[Test]
    public function all(): void
    {
        $values = new Values(new Values\Registry());
        (new QueueConsumersValues(false))->values($values);

        self::assertSame([
            'deployments' => [
                'internal-Mammatus-Queue-BuildIn-Noop-ae45abb14e21aa2ae051315fb47a7b12' => [
                    'name' => 'queue-worker-noop-ae45abb14e21aa2ae051315fb47a7b12',
                    'command' => 'mammatus-queue',
                    'arguments' => ['ae45abb14e21aa2ae051315fb47a7b12'],
                    'addOns' => [],
                ],
                'internal-Mammatus-Queue-BuildIn-Noop-noop_2' => [
                    'name' => 'queue-worker-noop-noop_2',
                    'command' => 'mammatus-queue',
                    'arguments' => ['03d9cb358a96e3cd6c0ec1759d39290b'],
                    'addOns' => [],
                ],
                'internal-Mammatus-Queue-BuildIn-Noop-noop_3' => [
                    'name' => 'queue-worker-noop-noop_3',
                    'command' => 'mammatus-queue',
                    'arguments' => ['8103cfd64b01225ca080d52daff46dea'],
                    'addOns' => [],
                ],
            ],
        ], $values->registry->get());
    }
}
