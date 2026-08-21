<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Encoder;

use Mammatus\Queue\Encoder\InvalidJSON;
use Mammatus\Queue\Encoder\JSON;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use const NAN;

final class JSONTest extends TestCase
{
    #[Test]
    public function encode(): void
    {
        self::assertSame('{"a":1}', new JSON()->encode(['a' => 1]));
    }

    #[Test]
    public function encodeInvalid(): void
    {
        self::expectException(InvalidJSON::class);
        self::expectExceptionMessageIsOrContains('Message is not valid JSON');

        new JSON()->encode(['a' => NAN]);
    }

    #[Test]
    public function decode(): void
    {
        self::assertSame(['a' => 1], new JSON()->decode('{"a":1}'));
    }

    #[Test]
    public function decodeInvalid(): void
    {
        self::expectException(InvalidJSON::class);
        self::expectExceptionMessageIsOrContains('Message is not valid JSON');

        new JSON()->decode('{]');
    }
}
