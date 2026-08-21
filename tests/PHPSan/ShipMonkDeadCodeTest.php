<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\PHPSan;

use Mammatus\DevApp\Queue\BeerMessage;
use Mammatus\DevApp\Queue\Noop;
use Mammatus\Queue\Encoder\JSON;
use Mammatus\Queue\PHPSan\ShipMonkDeadCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;
use WyriHaximus\TestUtilities\TestCase;

final class ShipMonkDeadCodeTest extends TestCase
{
    /** @return iterable<string, array{class-string, string, bool}> */
    public static function methodsProvider(): iterable
    {
        yield 'worker' => [Noop::class, 'perform', true];
        yield 'work' => [BeerMessage::class, '__construct', true];
        yield 'unrelated' => [JSON::class, 'encode', false];
    }

    /** @param class-string $className */
    #[Test]
    #[DataProvider('methodsProvider')]
    public function shouldMarkMethodAsUsed(string $className, string $methodName, bool $expected): void
    {
        $result = new ShipMonkDeadCode()->shouldMarkMethodAsUsed(new ReflectionMethod($className, $methodName));

        if ($expected) {
            self::assertInstanceOf(VirtualUsageData::class, $result);

            return;
        }

        self::assertNull($result);
    }
}
