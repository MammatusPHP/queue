<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue;

use Interop\Queue\Producer as InteropProducer;
use Mammatus\Queue\BuildIn\EmptyMessage;
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

            return $message->getBody() === '[]';
        })->once();

        $producer = new Producer($interOpProducer, new Hydrator(), new JSON());
        $producer->send(new EmptyMessage());
    }
}
