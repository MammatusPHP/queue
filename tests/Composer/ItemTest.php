<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Mammatus\Kubernetes\Attributes\Resources;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\BuildIn\EmptyMessage;
use Mammatus\Queue\BuildIn\Noop;
use Mammatus\Queue\Composer\Item;
use WyriHaximus\TestUtilities\TestCase;

use function Safe\json_encode;

final class ItemTest extends TestCase
{
    /** @test */
    public function json(): void
    {
        $item = new Item(
            Noop::class,
            'perform',
            EmptyMessage::class,
            new Consumer(
                'test',
                'test',
                EmptyMessage::class,
                1337,
                new Resources(
                    cpu: 0.666,
                    memory: 3,
                ),
            ),
            false,
        );
        self::assertSame(
            '{"class":"Mammatus\\\\Queue\\\\BuildIn\\\\Noop","method":"perform","dtoClass":"Mammatus\\\\Queue\\\\BuildIn\\\\EmptyMessage","consumer":{"addOns":[{"type":"container","helper":"mammatus.container.resources","arguments":{"cpu":"666m","memory":"3072Mi"}}],"friendlyName":"test","queue":"test","dtoClass":"Mammatus\\\\Queue\\\\BuildIn\\\\EmptyMessage","concurrency":1337},"split_out":false}',
            json_encode($item),
        );
    }
}
