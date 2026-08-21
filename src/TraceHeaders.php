<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

final class TraceHeaders
{
    /** @return array<string, string> */
    public static function inject(): array
    {
        $headers = [];
        TraceContextPropagator::getInstance()->inject($headers);

        // @phpstan-ignore return.type (inject() widens the by-ref carrier to mixed)
        return $headers;
    }

    /**
     * @param array<mixed> $headers
     *
     * @return callable(): void
     */
    public static function activate(array $headers): callable
    {
        $scope = TraceContextPropagator::getInstance()->extract($headers)->activate();

        return static function () use ($scope): void {
            $scope->detach();
        };
    }
}
