<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue\Producer as InteropProducer;
use Mammatus\DevApp\Queue\BeerMessage;
use Mammatus\Queue\Encoder\JSON;
use Mammatus\Queue\Hydrator;
use Mammatus\Queue\Message;
use Mammatus\Queue\Producer;
use Mammatus\Queue\Queue;
use Mockery;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

use function array_key_exists;

#[TimeOut(69)]
final class ProducerTest extends AsyncTestCase
{
    #[Test]
    public function send(): void
    {
        $interOpProducer = Mockery::mock(InteropProducer::class);
        $interOpProducer->shouldReceive('send')->withArgs(static function (Queue $queue, Message $message): bool {
            if ($queue->getQueueName() !== 'noop') {
                return false;
            }

            return $message->getBody() === '{"name":"Barcode Turquoise Orange","type":"Barrel Aged Stout","alcohol_percentage":15}' && $message->getHeaders() === [];
        })->once();

        $producer = new Producer($interOpProducer, new Hydrator(), new JSON());
        $producer->send(new BeerMessage(
            'Barcode Turquoise Orange',
            'Barrel Aged Stout',
            15,
        ));
    }

    #[Test]
    public function sendInjectsTraceHeadersWhenSpanIsActive(): void
    {
        $scope = Span::wrap(SpanContext::create(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            '00f067aa0ba902b7',
            TraceFlags::SAMPLED,
        ))->storeInContext(Context::getCurrent())->activate();

        try {
            $interOpProducer = Mockery::mock(InteropProducer::class);
            $interOpProducer->shouldReceive('send')->withArgs(static function (Queue $queue, Message $message): bool {
                if ($queue->getQueueName() !== 'noop') {
                    return false;
                }

                if ($message->getBody() !== '{"name":"Barcode Turquoise Orange","type":"Barrel Aged Stout","alcohol_percentage":15}') {
                    return false;
                }

                $headers = $message->getHeaders();

                return array_key_exists(TraceContextPropagator::TRACEPARENT, $headers)
                    && $headers[TraceContextPropagator::TRACEPARENT] === '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01';
            })->once();

            $producer = new Producer($interOpProducer, new Hydrator(), new JSON());
            $producer->send(new BeerMessage(
                'Barcode Turquoise Orange',
                'Barrel Aged Stout',
                15,
            ));
        } finally {
            $scope->detach();
        }
    }
}
