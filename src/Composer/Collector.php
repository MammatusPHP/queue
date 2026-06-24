<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use Mammatus\Kubernetes\Attributes\SplitOut;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Attributes\Consumers;
use Mammatus\Queue\Contracts\Work;
use Mammatus\Queue\Worker\Type;
use Realodix\ChangeCase\ChangeCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\ItemCollector;

use function array_key_exists;
use function array_map;
use function assert;
use function md5;
use function str_replace;

final class Collector implements ItemCollector
{
    private const int THE_NUMBER_OF_PARAMETERS_REQUIRED_FOR_A_METHOD_TO_BE_AN_EVENT_HANDLER_IS_ONE = 1;

    /** @return iterable<ItemContract> */
    public function collect(ReflectionClass $class): iterable
    {
        $attributes = [];
        foreach (new \ReflectionClass($class->getName())->getAttributes() as $attributeReflection) {
            $attributeSpiegel                       = $attributeReflection->newInstance();
            $attributes[$attributeSpiegel::class][] = $attributeSpiegel;
        }

        if (! array_key_exists(Consumer::class, $attributes) && ! array_key_exists(Consumers::class, $attributes)) {
            return;
        }

        /** @var array<Consumer> $consumerAttributes */
        $consumerAttributes = [];

        if (array_key_exists(Consumer::class, $attributes)) {
            foreach ($attributes[Consumer::class] as $consumerAttribute) {
                assert($consumerAttribute instanceof Consumer);
                $consumerAttributes[] = $consumerAttribute;
            }
        }

        if (array_key_exists(Consumers::class, $attributes)) {
            foreach ($attributes[Consumers::class] as $consumersAttribute) {
                assert($consumersAttribute instanceof Consumers);
                foreach ($consumersAttribute->consumers as $consumerAttribute) {
                    $consumerAttributes[] = $consumerAttribute;
                }
            }
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
                if (! new \ReflectionClass($messageDTO)->implementsInterface(Work::class)) {
                    continue;
                }

                foreach ($consumerAttributes as $attribute) {
                    if ($attribute->dtoClass !== $messageDTO) {
                        continue;
                    }

                    $generateClassesClassNameSuffix = ChangeCase::pascal(
                        $class->getName() . '_Via_' . $method->getName() . '_For_' . $attribute->queue . '_With_' . $messageDTO . ($attribute->friendlyName !== '' ? '_As_' . $attribute->friendlyName : ''),
                    );
                    $hash                           = md5($generateClassesClassNameSuffix);

                    yield new Item(
                        $hash,
                        $class->getName(),
                        $method->getName(),
                        $messageDTO,
                        str_replace(['.', '_'], '-', ChangeCase::snake($generateClassesClassNameSuffix) . '-' . ($attribute->friendlyName !== '' ? $attribute->friendlyName : $hash)),
                        $generateClassesClassNameSuffix,
                        str_replace(['.', '_'], '-', 'queue-worker-' . $attribute->queue . '-' . ($attribute->friendlyName !== '' ? $attribute->friendlyName : $hash)),
                        $attribute,
                        array_key_exists(SplitOut::class, $attributes) ? Type::Kubernetes : Type::Daemon,
                    );
                }
            }
        }
    }
}
