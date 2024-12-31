<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use JsonSerializable;
use Mammatus\Queue\Attributes\Consumer;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

final readonly class Item implements ItemContract, JsonSerializable
{
    /** @param class-string $class */
    public function __construct(
        public string $class,
        public string $method,
        public string $dtoClass,
        public Consumer $consumer,
        public bool $splitOut,
    ) {
    }

    /** @return array{class: class-string, consumer: Consumer, split_out: bool} */
    public function jsonSerialize(): array
    {
        return [
            'class' => $this->class,
            'method' => $this->method,
            'dtoClass' => $this->dtoClass,
            'consumer' => $this->consumer,
            'split_out' => $this->splitOut,
        ];
    }
}
