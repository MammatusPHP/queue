<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use JsonSerializable;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Worker\Type;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

final readonly class Item implements ItemContract, JsonSerializable
{
    /** @param class-string $class */
    public function __construct(
        public string $hash,
        public string $class,
        public string $method,
        public string $dtoClass,
        public string $generatedIndex,
        public string $generateClassesClassNameSuffix,
        public string $groupName,
        public Consumer $consumer,
        public Type $type,
    ) {
    }

    /** @return array{hash: string, class: string, method: string, dtoClass: string, generateClassesClassNameSuffix: string, groupName: string, consumer: Consumer, type: string} */
    public function jsonSerialize(): array
    {
        return [
            'hash' => $this->hash,
            'class' => $this->class,
            'method' => $this->method,
            'dtoClass' => $this->dtoClass,
            'generatedIndex' => $this->generatedIndex,
            'generateClassesClassNameSuffix' => $this->generateClassesClassNameSuffix,
            'groupName' => $this->groupName,
            'consumer' => $this->consumer,
            'type' => $this->type->value,
        ];
    }
}
