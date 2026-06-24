<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Composer\IO\NullIO;
use RuntimeException;
use Symfony\Component\Console\Output\StreamOutput;

use function fopen;
use function fseek;
use function is_resource;
use function stream_get_contents;

final class IOMock extends NullIO
{
    private readonly StreamOutput $output;

    public function __construct()
    {
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fopen */
        $resource = fopen('php://memory', 'rw');
        if (! is_resource($resource)) {
            throw new RuntimeException('Could not open memory stream');
        }

        $this->output = new StreamOutput($resource, decorated: false);
    }

    public function output(): string
    {
        fseek($this->output->getStream(), 0);

        return stream_get_contents($this->output->getStream());
    }

    /** @inheritDoc */
    public function write($messages, bool $newline = true, int $verbosity = self::NORMAL): void
    {
        $this->output->write($messages, $newline, $verbosity & StreamOutput::OUTPUT_RAW);
    }
}
