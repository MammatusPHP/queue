<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Composer\IO\NullIO;
use Symfony\Component\Console\Output\StreamOutput;

use function fseek;
use function Safe\fopen;
use function Safe\stream_get_contents;

final class IOMock extends NullIO
{
    private readonly StreamOutput $output;

    public function __construct()
    {
        $this->output = new StreamOutput(fopen('php://memory', 'rw'), decorated: false);
    }

    public function output(): string
    {
        fseek($this->output->getStream(), 0);

        return stream_get_contents($this->output->getStream());
    }

            /**
             * @inheritDoc
             * @phpstan-ignore typeCoverage.paramTypeCoverage
             */
    public function write($messages, bool $newline = true, int $verbosity = self::NORMAL): void
    {
        $this->output->write($messages, $newline, $verbosity & StreamOutput::OUTPUT_RAW);
    }
}
