<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\DevApp\Queue\Noop;
use Mammatus\Kubernetes\Attributes\Resources;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Composer\Item;
use Mammatus\Queue\Worker\Type;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function Safe\json_encode;

final class ItemTest extends TestCase
{
    #[Test]
    public function json(): void
    {
        $item = new Item(
            'brownie',
            Noop::class,
            'perform',
            EmptyMessage::class,
            'queue-worker-space-cake',
            'SpaceCake',
            'queue-worker-space-cake',
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
            Type::Internal,
        );
        self::assertSame(
            '{"hash":"brownie","class":"Mammatus\\\\DevApp\\\\Queue\\\\Noop","method":"perform","dtoClass":"Mammatus\\\\DevApp\\\\Queue\\\\EmptyMessage","generatedIndex":"queue-worker-space-cake","generateClassesClassNameSuffix":"SpaceCake","groupName":"queue-worker-space-cake","consumer":{"addOns":[{"type":"container","helper":"mammatus.container.resources","arguments":{"cpu":"666m","memory":"3072Mi"}}],"friendlyName":"test","queue":"test","dtoClass":"Mammatus\\\\DevApp\\\\Queue\\\\EmptyMessage","concurrency":1337},"type":"internal"}',
            json_encode($item),
        );
    }
}
