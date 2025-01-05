<?php

declare(strict_types=1);

namespace Mammatus\Queue\Encoder;

use Mammatus\Queue\Contracts\Encoder;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function json_last_error_msg;

final class JSON implements Encoder
{
    /** @inheritdoc */
    public function encode(array $payload): string
    {
        $json = json_encode($payload);
        if (! is_string($json)) {
            throw new InvalidJSON('Message is not valid JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /** @inheritdoc */
    public function decode(string $payload): array
    {
        /** @var array<string, mixed> $json */
        $json = json_decode($payload, true);
        /** @phpstan-ignore-next-line */
        if (! is_array($json)) {
            throw new InvalidJSON('Message is not valid JSON: ' . json_last_error_msg());
        }

        return $json;
    }
}
