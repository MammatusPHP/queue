<?php

declare(strict_types=1);

namespace Mammatus\Queue\Generated;

use Mammatus\Queue\Worker;

// phpcs:disable
/**
 * Autogenerated file, do not edit. Changes will be overwritten on the next composer (install|update)
 */
abstract class AbstractList
{
    /**
     * @return iterable<string, Worker>
     */
    final protected function workers(): iterable
    {
        /** @see \Mammatus\Queue\BuildIn\Noop */
        yield 'internal-Mammatus-Queue-BuildIn-Noop' => new Worker(
            'internal',
            'noop',
            1,
            \Mammatus\Queue\BuildIn\Noop::class,
            'perform',
            \Mammatus\Queue\BuildIn\EmptyMessage::class,
            \json_decode('[]', true), /** @phpstan-ignore-line */
        );
    }
}
