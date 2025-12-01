<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue\Producer as InteropProducer;
use Mammatus\DevApp\Queue\BeerMessage;
use Mammatus\Queue\Encoder\JSON;
use Mammatus\Queue\Generated\Hydrator;
use Mammatus\Queue\Message;
use Mammatus\Queue\Producer;
use Mammatus\Queue\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PHPUnit\TimeOut;

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
}
