<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Interop\Queue\Producer as InteropProducer;
use Mammatus\Queue\Contracts\Encoder;
use Mammatus\Queue\Contracts\Producer as ProducerContract;
use Mammatus\Queue\Contracts\Work;
use Mammatus\Queue\Generated\Hydrator;
use Mammatus\Queue\Generated\WorkQueueMap;
//use OpenTelemetry\API\Instrumentation\WithSpan;
use RuntimeException;

use function gettype;
use function is_array;

final class Producer extends WorkQueueMap implements ProducerContract
{
    public function __construct(
        private readonly InteropProducer $producer,
        private readonly Hydrator $hydrator,
        private readonly Encoder $encoder,
    ) {
    }

    #[WithSpan]
    public function send(Work $work): void
    {
        $message = new Message();
        $message->setBody($this->encodeMessage($this->serializeMessage($work)));
        $message->setHeaders([]);

        $this->producer->send(
            new Queue($this->lookUp($work)),
            $message,
        );
    }

    /** @return array<string, mixed> */
    #[WithSpan]
    private function serializeMessage(
        Work $work,
    ): array {
        /** @var array<string, mixed> $array */
        $array = $this->hydrator->serializeObject($work);
        /** @phpstan-ignore-next-line */
        if (! is_array($array)) {
            throw new RuntimeException('Message isn\'t translated into an array but ' . gettype($array) . ' instead');
        }

        return $array;
    }

    /** @param array<string, mixed> $message */
    #[WithSpan]
    private function encodeMessage(array $message): string
    {
        return $this->encoder->encode($message);
    }
}
