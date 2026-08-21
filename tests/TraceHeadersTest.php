<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Mammatus\Queue\TraceHeaders;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function array_key_exists;

final class TraceHeadersTest extends TestCase
{
    #[Test]
    public function injectWithoutActiveSpanYieldsEmptyHeaders(): void
    {
        self::assertSame([], TraceHeaders::inject());
    }

    #[Test]
    public function injectWithActiveSpanYieldsTraceparent(): void
    {
        $scope = Span::wrap(SpanContext::create(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            '00f067aa0ba902b7',
            TraceFlags::SAMPLED,
        ))->storeInContext(Context::getCurrent())->activate();

        try {
            $headers = TraceHeaders::inject();
        } finally {
            $scope->detach();
        }

        self::assertTrue(array_key_exists(TraceContextPropagator::TRACEPARENT, $headers));
        self::assertSame(
            '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01',
            $headers[TraceContextPropagator::TRACEPARENT],
        );
    }

    #[Test]
    public function activateMakesRemoteSpanCurrent(): void
    {
        $detach = TraceHeaders::activate([TraceContextPropagator::TRACEPARENT => '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01']);

        try {
            $spanContext = Span::fromContext(Context::getCurrent())->getContext();
        } finally {
            $detach();
        }

        self::assertTrue($spanContext->isValid());
        self::assertTrue($spanContext->isRemote());
        self::assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $spanContext->getTraceId());
        self::assertSame('00f067aa0ba902b7', $spanContext->getSpanId());
    }

    #[Test]
    public function activateWithoutHeadersLeavesContextUntouched(): void
    {
        $before = Span::fromContext(Context::getCurrent())->getContext();
        $detach = TraceHeaders::activate([]);

        try {
            $during = Span::fromContext(Context::getCurrent())->getContext();
        } finally {
            $detach();
        }

        self::assertSame($before->getTraceId(), $during->getTraceId());
        self::assertSame($before->getSpanId(), $during->getSpanId());
    }
}
