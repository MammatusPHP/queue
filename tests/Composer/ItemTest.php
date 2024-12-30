<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Mammatus\Kubernetes\Attributes\Resources;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Composer\Item;
use WyriHaximus\TestUtilities\TestCase;

use function Safe\json_encode;

final class ItemTest extends TestCase
{
    /** @test */
    public function json(): void
    {
        $item = new Item(
            Item::class,
            new Consumer(
                'test',
                1337,
                new Resources(
                    cpu: 0.666,
                    memory: 3,
                ),
            ),
            false,
        );
        self::assertSame(
            '{"class":"Mammatus\\\\Queue\\\\Composer\\\\Item","consumer":{"addOns":[{"type":"container","helper":"mammatus.container.resources","arguments":{"cpu":"666m","memory":"3072Mi"}}],"queue":"test","concurrency":1337},"split_out":false}',
            json_encode($item),
        );
    }
}
