<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Mammatus\DevApp\Queue\Bar;
use Mammatus\DevApp\Queue\BeerMessage;
use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\DevApp\Queue\Noop;
use Mammatus\DevApp\Queue\OHellNo;
use Mammatus\Groups\Type;
use Mammatus\Queue\Composer\Collector;
use Mammatus\Queue\Composer\Item;
use Mammatus\Tests\Queue\Composer\Fixture\BothConsumerAttributesWorker;
use Mammatus\Tests\Queue\Composer\Fixture\DestructorBeforeMethodWorker;
use Mammatus\Tests\Queue\Composer\Fixture\IntersectionWorker;
use Mammatus\Tests\Queue\Composer\Fixture\MultiConsumerWorker;
use Mammatus\Tests\Queue\Composer\Fixture\MultiMethodWorker;
use Mammatus\Tests\Queue\Composer\Fixture\NoAttributes;
use Mammatus\Tests\Queue\Composer\Fixture\NonWorkDtoWorker;
use Mammatus\Tests\Queue\Composer\Fixture\OnlyConsumersWorker;
use Mammatus\Tests\Queue\Composer\Fixture\SplitOutWorker;
use Mammatus\Tests\Queue\Composer\Fixture\TwoParameterBeforeOneParameterWorker;
use Mammatus\Tests\Queue\Composer\Fixture\UnionNonWorkFirstWorker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Realodix\ChangeCase\ChangeCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use WyriHaximus\TestUtilities\TestCase;

final class CollectorTest extends TestCase
{
    /** @return iterable<string, array{class-string, int, Type}> */
    public static function workersProvider(): iterable
    {
        yield 'noop' => [Noop::class, 3, Type::Daemon];
        yield 'bar' => [Bar::class, 2, Type::Daemon];
        yield 'split-out' => [SplitOutWorker::class, 1, Type::Normal];
        yield 'ohellno' => [OHellNo::class, 0, Type::Daemon];
        yield 'no-attributes' => [NoAttributes::class, 0, Type::Daemon];
        yield 'non-work-dto' => [NonWorkDtoWorker::class, 0, Type::Daemon];
        yield 'intersection' => [IntersectionWorker::class, 0, Type::Daemon];
        yield 'multi-method' => [MultiMethodWorker::class, 2, Type::Daemon];
        yield 'union-non-work-first' => [UnionNonWorkFirstWorker::class, 1, Type::Daemon];
        yield 'only-consumers' => [OnlyConsumersWorker::class, 1, Type::Daemon];
        yield 'both-consumer-attributes' => [BothConsumerAttributesWorker::class, 2, Type::Daemon];
        yield 'multi-consumer' => [MultiConsumerWorker::class, 1, Type::Daemon];
        yield 'two-parameter-before-one-parameter' => [TwoParameterBeforeOneParameterWorker::class, 1, Type::Daemon];
        yield 'destructor-before-method' => [DestructorBeforeMethodWorker::class, 1, Type::Daemon];
    }

    /** @param class-string $className */
    #[Test]
    #[DataProvider('workersProvider')]
    public function collect(string $className, int $expectedCount, Type $expectedType): void
    {
        $items = [...new Collector()->collect(ReflectionClass::createFromName($className))];

        self::assertCount($expectedCount, $items);

        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertSame($className, $item->class);
            self::assertSame($expectedType, $item->type);
            self::assertContains($item->dtoClass, [EmptyMessage::class, BeerMessage::class]);
        }
    }

    #[Test]
    public function collectGeneratesExpectedClassNameSuffix(): void
    {
        $items = [...new Collector()->collect(ReflectionClass::createFromName(SplitOutWorker::class))];

        self::assertCount(1, $items);

        $item = $items[0];
        self::assertInstanceOf(Item::class, $item);

        $expectedSuffix = ChangeCase::pascal(
            SplitOutWorker::class . '_Via_perform_For_noop_With_' . EmptyMessage::class . '_As_split',
        );

        self::assertSame($expectedSuffix, $item->generateClassesClassNameSuffix);
        self::assertSame('perform', $item->method);
        self::assertSame('split', $item->consumer->friendlyName);
    }
}
