<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use Mammatus\Kubernetes\Attributes\SplitOut;
use Mammatus\Queue\Attributes\Consumer;
use Roave\BetterReflection\Reflection\ReflectionClass;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\ItemCollector;

use function array_key_exists;

final class Collector implements ItemCollector
{
    /** @return iterable<ItemContract> */
    public function collect(ReflectionClass $class): iterable
    {
        $attributes = [];
        foreach ((new \ReflectionClass($class->getName()))->getAttributes() as $attributeReflection) {
            $attribute                     = $attributeReflection->newInstance();
            $attributes[$attribute::class] = $attribute;
        }

        if (! array_key_exists(Consumer::class, $attributes)) {
            return;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        yield new Item(
            $class->getName(),
            $attributes[Consumer::class], /** @phpstan-ignore-line */
            array_key_exists(SplitOut::class, $attributes),
        );
    }
}
