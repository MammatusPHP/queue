<?php

declare(strict_types=1);

namespace Mammatus\Queue;

use Interop\Queue as QueueInterop;
use Interop\Queue\Message;
use Mammatus\Queue\Contracts\Encoder;
use Mammatus\Queue\Contracts\Worker as WorkerContract;
use OpenTelemetry\API\Instrumentation\SpanAttribute;
use OpenTelemetry\API\Instrumentation\WithSpan;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use Throwable;
use WyriHaximus\Broadcast\Contracts\Listener;
use WyriHaximus\PSR3\CallableThrowableLogger\CallableThrowableLogger;
use WyriHaximus\PSR3\ContextLogger\ContextLogger;

use function React\Async\async;
use function React\Async\await;
use function React\Promise\all;
use function React\Promise\Timer\sleep;
use function WyriHaximus\React\futurePromise;

final class Consumer implements Listener
{
    private bool $running = true;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly QueueInterop\Context $context,
        private readonly Hydrator $hydrator,
        private readonly Encoder $encoder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function close(): void
    {
        $this->running = false;
    }

    /** @return PromiseInterface<mixed> */
    public function setupConsumer(Worker $worker): PromiseInterface
    {
        $logger = new ContextLogger($this->logger, [
            'worker' => $worker->class,
            'method' => $worker->method,
            'queue' => $worker->queue,
            'dtoClass' => $worker->dtoClass,
        ]);

        $logger->debug('Setting up logger for {worker}');

        $logger->debug('Getting worker instance for {worker}');
        $workerInstance = $this->container->get($worker->class);
        if (! ($workerInstance instanceof WorkerContract)) {
            throw new RuntimeException('Worker instance must be instance of ' . WorkerContract::class);
        }

        $promises = [
            sleep(1),
        ];
        $logger->debug('Starting {concurrency} workers for queue {queue} with DTO {dtoClass}', [
            'concurrency' => $worker->concurrency,
        ]);
        for ($i = 0; $i < $worker->concurrency; $i++) {
            $logger->info('Starting consumer {index} of {concurrency} for queue {queue} with DTO {dtoClass}', [
                'index' => $i + 1,
                'concurrency' => $worker->concurrency,
            ]);
            $promises[] = async(fn (): bool => $this->consume($worker, $workerInstance, new ContextLogger($logger, ['fiber' => $i])))();
        }

        return all($promises);
    }

    private function consume(Worker $worker, WorkerContract $workerInstance, LoggerInterface $baseLogger): true
    {
        $consumer = $this->context->createConsumer(new Queue($worker->queue));
        while ($this->running) {
            $message = $consumer->receiveNoWait();
            if (! $message instanceof Message) {
                await(futurePromise());
                continue;
            }

            $detach = TraceHeaders::activate($message->getHeaders());
            try {
                $this->handleMessage($message, $consumer, $worker, $workerInstance, $baseLogger);
            } finally {
                $detach();
            }

            await(futurePromise());
        }

        return true;
    }

    #[WithSpan]
    private function handleMessage(Message $message, \Interop\Queue\Consumer $consumer, Worker $worker, WorkerContract $workerInstance, LoggerInterface $baseLogger): void
    {
        try {
            $baseLogger->debug('Hydrating message');
            $dto = $this->hydrateMessage(
                $worker,
                $this->decodeMessage($message),
            );

            $baseLogger->debug('Invoking worker');
            $workerInstance->{$worker->method}($dto);

            $baseLogger->debug('Acknowledging message');
            $consumer->acknowledge($message);
        } catch (Throwable $error) {
            $baseLogger->debug('Rejecting message');
            $consumer->reject($message);
            CallableThrowableLogger::create($baseLogger)($error);
        }
    }

    /** @param array<mixed> $message */
    #[WithSpan]
    private function hydrateMessage(
        #[SpanAttribute]
        Worker $worker,
        array $message,
    ): object {
        return $this->hydrator->hydrateObject(
            $worker->dtoClass,
            $message,
        );
    }

    /** @return array<mixed> */
    #[WithSpan]
    private function decodeMessage(Message $message): array
    {
        return $this->encoder->decode($message->getBody());
    }
}
