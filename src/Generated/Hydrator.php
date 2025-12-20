<?php

declare(strict_types=1);

namespace Mammatus\Queue\Generated;

use EventSauce\ObjectHydrator\IterableList;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use EventSauce\ObjectHydrator\UnableToSerializeObject;
use Generator;

class Hydrator implements ObjectMapper
{
    private array $hydrationStack = [];
    public function __construct() {}

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public function hydrateObject(string $className, array $payload): object
    {
        return match($className) {
            'Mammatus\DevApp\Queue\BeerMessage' => $this->hydrateMammatus⚡️DevApp⚡️Queue⚡️BeerMessage($payload),
                'Mammatus\DevApp\Queue\EmptyMessage' => $this->hydrateMammatus⚡️DevApp⚡️Queue⚡️EmptyMessage($payload),
            default => throw UnableToHydrateObject::noHydrationDefined($className, $this->hydrationStack),
        };
    }
    
            
    private function hydrateMammatus⚡️DevApp⚡️Queue⚡️BeerMessage(array $payload): \Mammatus\DevApp\Queue\BeerMessage
    {
        $properties = []; 
        $missingFields = [];
        try {
            $value = $payload['name'] ?? null;

            if ($value === null) {
                $missingFields[] = 'name';
                goto after_name;
            }

            $properties['name'] = $value;

            after_name:

            $value = $payload['type'] ?? null;

            if ($value === null) {
                $missingFields[] = 'type';
                goto after_type;
            }

            $properties['type'] = $value;

            after_type:

            $value = $payload['alcohol_percentage'] ?? null;

            if ($value === null) {
                $missingFields[] = 'alcohol_percentage';
                goto after_alcoholPercentage;
            }

            $properties['alcoholPercentage'] = $value;

            after_alcoholPercentage:

        } catch (\Throwable $exception) {
            throw UnableToHydrateObject::dueToError('Mammatus\DevApp\Queue\BeerMessage', $exception, stack: $this->hydrationStack);
        }

        if (count($missingFields) > 0) {
            throw UnableToHydrateObject::dueToMissingFields(\Mammatus\DevApp\Queue\BeerMessage::class, $missingFields, stack: $this->hydrationStack);
        }

        try {
            return new \Mammatus\DevApp\Queue\BeerMessage(...$properties);
        } catch (\Throwable $exception) {
            throw UnableToHydrateObject::dueToError('Mammatus\DevApp\Queue\BeerMessage', $exception, stack: $this->hydrationStack);
        }
    }

        
    private function hydrateMammatus⚡️DevApp⚡️Queue⚡️EmptyMessage(array $payload): \Mammatus\DevApp\Queue\EmptyMessage
    {
        $properties = []; 
        $missingFields = [];
        try {
        } catch (\Throwable $exception) {
            throw UnableToHydrateObject::dueToError('Mammatus\DevApp\Queue\EmptyMessage', $exception, stack: $this->hydrationStack);
        }

        if (count($missingFields) > 0) {
            throw UnableToHydrateObject::dueToMissingFields(\Mammatus\DevApp\Queue\EmptyMessage::class, $missingFields, stack: $this->hydrationStack);
        }

        try {
            return new \Mammatus\DevApp\Queue\EmptyMessage(...$properties);
        } catch (\Throwable $exception) {
            throw UnableToHydrateObject::dueToError('Mammatus\DevApp\Queue\EmptyMessage', $exception, stack: $this->hydrationStack);
        }
    }
    
    private function serializeViaTypeMap(string $accessor, object $object, array $payloadToTypeMap): array
    {
        foreach ($payloadToTypeMap as $payloadType => [$valueType, $method]) {
            if (is_a($object, $valueType)) {
                return [$accessor => $payloadType] + $this->{$method}($object);
            }
        }

        throw new \LogicException('No type mapped for object of class: ' . get_class($object));
    }

    public function serializeObject(object $object): mixed
    {
        return $this->serializeObjectOfType($object, get_class($object));
    }

    /**
     * @template T
     *
     * @param T               $object
     * @param class-string<T> $className
     */
    public function serializeObjectOfType(object $object, string $className): mixed
    {
        try {
            return match($className) {
                'array' => $this->serializeValuearray($object),
            'Ramsey\Uuid\UuidInterface' => $this->serializeValueRamsey⚡️Uuid⚡️UuidInterface($object),
            'DateTime' => $this->serializeValueDateTime($object),
            'DateTimeImmutable' => $this->serializeValueDateTimeImmutable($object),
            'DateTimeInterface' => $this->serializeValueDateTimeInterface($object),
            'Mammatus\DevApp\Queue\BeerMessage' => $this->serializeObjectMammatus⚡️DevApp⚡️Queue⚡️BeerMessage($object),
            'Mammatus\DevApp\Queue\EmptyMessage' => $this->serializeObjectMammatus⚡️DevApp⚡️Queue⚡️EmptyMessage($object),
                default => throw new \LogicException("No serialization defined for $className"),
            };
        } catch (\Throwable $exception) {
            throw UnableToSerializeObject::dueToError($className, $exception);
        }
    }
    
    
    private function serializeValuearray(mixed $value): mixed
    {
        static $serializer;
        
        if ($serializer === null) {
            $serializer = new \EventSauce\ObjectHydrator\PropertySerializers\SerializeArrayItems(...array (
));
        }
        
        return $serializer->serialize($value, $this);
    }


    private function serializeValueRamsey⚡️Uuid⚡️UuidInterface(mixed $value): mixed
    {
        static $serializer;
        
        if ($serializer === null) {
            $serializer = new \EventSauce\ObjectHydrator\PropertySerializers\SerializeUuidToString(...array (
));
        }
        
        return $serializer->serialize($value, $this);
    }


    private function serializeValueDateTime(mixed $value): mixed
    {
        static $serializer;
        
        if ($serializer === null) {
            $serializer = new \EventSauce\ObjectHydrator\PropertySerializers\SerializeDateTime(...array (
));
        }
        
        return $serializer->serialize($value, $this);
    }


    private function serializeValueDateTimeImmutable(mixed $value): mixed
    {
        static $serializer;
        
        if ($serializer === null) {
            $serializer = new \EventSauce\ObjectHydrator\PropertySerializers\SerializeDateTime(...array (
));
        }
        
        return $serializer->serialize($value, $this);
    }


    private function serializeValueDateTimeInterface(mixed $value): mixed
    {
        static $serializer;
        
        if ($serializer === null) {
            $serializer = new \EventSauce\ObjectHydrator\PropertySerializers\SerializeDateTime(...array (
));
        }
        
        return $serializer->serialize($value, $this);
    }


    private function serializeObjectMammatus⚡️DevApp⚡️Queue⚡️BeerMessage(mixed $object): mixed
    {
        \assert($object instanceof \Mammatus\DevApp\Queue\BeerMessage);
        $result = [];

        $name = $object->name;
        after_name:        $result['name'] = $name;

        
        $type = $object->type;
        after_type:        $result['type'] = $type;

        
        $alcoholPercentage = $object->alcoholPercentage;
        after_alcoholPercentage:        $result['alcohol_percentage'] = $alcoholPercentage;


        return $result;
    }


    private function serializeObjectMammatus⚡️DevApp⚡️Queue⚡️EmptyMessage(mixed $object): mixed
    {
        \assert($object instanceof \Mammatus\DevApp\Queue\EmptyMessage);
        $result = [];


        return $result;
    }
    
    

    /**
     * @template T
     *
     * @param class-string<T> $className
     * @param iterable<array> $payloads;
     *
     * @return IterableList<T>
     *
     * @throws UnableToHydrateObject
     */
    public function hydrateObjects(string $className, iterable $payloads): IterableList
    {
        return new IterableList($this->doHydrateObjects($className, $payloads));
    }

    private function doHydrateObjects(string $className, iterable $payloads): Generator
    {
        foreach ($payloads as $index => $payload) {
            yield $index => $this->hydrateObject($className, $payload);
        }
    }

    /**
     * @template T
     *
     * @param class-string<T> $className
     * @param iterable<array> $payloads;
     *
     * @return IterableList<T>
     *
     * @throws UnableToSerializeObject
     */
    public function serializeObjects(iterable $payloads): IterableList
    {
        return new IterableList($this->doSerializeObjects($payloads));
    }

    private function doSerializeObjects(iterable $objects): Generator
    {
        foreach ($objects as $index => $object) {
            yield $index => $this->serializeObject($object);
        }
    }
}