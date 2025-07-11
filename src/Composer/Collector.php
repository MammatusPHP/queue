<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use Mammatus\Kubernetes\Attributes\SplitOut;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Work;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\ItemCollector;

use function array_key_exists;
use function array_map;

final class Collector implements ItemCollector
{
    private const int THE_NUMBER_OF_PARAMETERS_REQUIRED_FOR_A_METHOD_TO_BE_AN_EVENT_HANDLER_IS_ONE = 1;

    /** @return iterable<ItemContract> */
    public function collect(ReflectionClass $class): iterable
    {
        $attributes = [];
        foreach ((new \ReflectionClass($class->getName()))->getAttributes() as $attributeReflection) {
            $attribute                       = $attributeReflection->newInstance();
            $attributes[$attribute::class][] = $attribute;
        }

        if (! array_key_exists(Consumer::class, $attributes)) {
            return;
        }

        foreach ($class->getMethods() as $method) {
            if (! $method->isPublic()) {
                continue;
            }

            if ($method->isConstructor()) {
                continue;
            }

            if ($method->isDestructor()) {
                continue;
            }

            if ($method->getNumberOfParameters() !== self::THE_NUMBER_OF_PARAMETERS_REQUIRED_FOR_A_METHOD_TO_BE_AN_EVENT_HANDLER_IS_ONE) {
                continue;
            }

            $messageDTOHolder = $method->getParameters()[0]->getType();
            if ($messageDTOHolder instanceof ReflectionIntersectionType) {
                continue;
            }

            if ($messageDTOHolder instanceof ReflectionUnionType) {
                /** @var array<class-string<Work>> $messageDTOs */
                $messageDTOs = array_map(static fn (ReflectionType $reflectedType): string => (string) $reflectedType, $messageDTOHolder->getTypes());
            } else {
                /** @var array<class-string<Work>> $messageDTOs */
                $messageDTOs = [(string) $messageDTOHolder];
            }

            foreach ($messageDTOs as $messageDTO) {
                if (! (new \ReflectionClass($messageDTO))->implementsInterface(Work::class)) {
                    continue;
                }

                foreach ($attributes[Consumer::class] as $attribute) {
                    /** @psalm-suppress ArgumentTypeCoercion */
                    yield new Item(
                        $class->getName(),
                        $method->getName(),
                        $messageDTO,
                        $attribute, /** @phpstan-ignore-line */
                        array_key_exists(SplitOut::class, $attributes),
                    );
                }
            }
        }
    }
}
