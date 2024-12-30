<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Kubernetes\Helm;

use Mammatus\Kubernetes\Events\Helm\Values;
use Mammatus\Queue\Kubernetes\Helm\QueueConsumersValues;
use WyriHaximus\TestUtilities\TestCase;

final class CronJobsValuesTest extends TestCase
{
    /** @test */
    public function none(): void
    {
        $values = new Values(new Values\Registry());
        (new QueueConsumersValues())->values($values);

        self::assertSame([
            'deployments' => [], // Empty array here because we don't have any default deployments running in Kubernetes out of the box
        ], $values->registry->get());
    }

    /** @test */
    public function all(): void
    {
        $values = new Values(new Values\Registry());
        (new QueueConsumersValues(false))->values($values);

        self::assertSame([
            'deployments' => [
                'internal-Mammatus-Queue-BuildIn-Noop' => [
                    'name' => 'queue-worker-noop',
                    'command' => 'mammatus-queue',
                    'arguments' => ['Mammatus\Queue\BuildIn\Noop'],
                    'addOns' => [],
                ],
            ],
        ], $values->registry->get());
    }
}
