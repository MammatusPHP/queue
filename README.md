# mammatus/queue

⏱️ Queue manager: async queue worker infrastructure with code generation.

## What it does

This package has two sides:

**Code generation (Composer plugin):** On every `composer install` or `composer update`, the plugin scans all classes implementing `Mammatus\Queue\Contracts\Worker` that are annotated with `#[Consumer]` or `#[Consumers]` attributes. For each worker/method/DTO combination it generates:

- A `Consumer` class: a `LifeCycleHandler` that starts and stops the queue consumer
- A `WorkerFactory` class: factory that creates the `Worker` configuration
- A `Producer` class: serializes DTOs, encodes them, and sends them to the correct queue
- A `Hydrator` class: EventSauce object hydrator for all DTOs
- Kubernetes Helm `GroupAddons`: adds worker add-ons to Helm values
- Terraform queue list: registers queue names as Terraform variables

**Runtime:** The `Consumer` class polls queues, decodes messages, hydrates them into DTOs, and invokes the worker method. It supports concurrency, OpenTelemetry tracing, and structured logging.

## Setup

Add the package and enable the plugin in `composer.json`:

```json
{
  "extra": {
    "mammatus": {
      "queue": {
        "has-workers": true
      }
    }
  }
}
```

The `"has-workers": true` flag tells the plugin to scan for workers. Without it, no code is generated.

## Defining workers

Create a class implementing `Mammatus\Queue\Contracts\Worker` and annotate it with `#[Consumer]` or `#[Consumers]`:

```php
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;

#[Consumer(friendlyName: 'my-worker', queue: 'my-queue', dtoClass: MyMessage::class, concurrency: 4)]
final class MyWorker implements Worker
{
    public function handle(MyMessage $work): void
    {
        // Process the message
    }
}
```

The `#[Consumer]` attribute controls:

| Property       | Description                                          |
|----------------|------------------------------------------------------|
| `queue`        | Queue name to consume from                           |
| `dtoClass`     | The message DTO class this consumer handles          |
| `concurrency`  | Number of parallel consumers for this queue          |
| `friendlyName` | Human-readable suffix for generated class names      |

Use `#[Consumers]` to group multiple `#[Consumer]` attributes:

```php
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Attributes\Consumers;
use Mammatus\Queue\Contracts\Worker;

#[Consumers(
    new Consumer(friendlyName: 'fast', queue: 'priority', dtoClass: FastMessage::class, concurrency: 8),
    new Consumer(friendlyName: 'slow', queue: 'background', dtoClass: SlowMessage::class, concurrency: 2),
)]
final class MultiWorker implements Worker
{
    public function process(FastMessage|SlowMessage $work): void
    {
        // Handle both message types
    }
}
```

Worker methods must be public, accept exactly one parameter, and that parameter must implement `Mammatus\Queue\Contracts\Work`. Union types are supported, and each type in the union is matched against the `dtoClass` in the `#[Consumer]` attribute.

## Defining messages

Messages are plain DTOs implementing `Mammatus\Queue\Contracts\Work`:

```php
use Mammatus\Queue\Contracts\Work;

final class MyMessage implements Work
{
    public function __construct(
        public readonly string $id,
        public readonly string $payload,
    ) {
    }
}
```

## Sending messages

Use the generated `Mammatus\Queue\Producer` to send messages:

```php
use Mammatus\Queue\Producer;

$producer->send(new MyMessage('abc-123', 'Hello'));
```

The producer serializes the message, encodes it, injects OpenTelemetry trace headers, and routes it to the correct queue based on the DTO class.

## Generated files

All generated files live in `src/` and are regenerated on every `composer install` or `composer update`:

| File                              | Purpose                                      |
|-----------------------------------|----------------------------------------------|
| `src/Consumer/*.php`              | Per-worker consumer lifecycle handlers        |
| `src/Consumer/WorkerFactory/*.php`| Worker configuration factories                |
| `src/Producer.php`                | Message sender with queue routing             |
| `src/Hydrator.php`                | EventSauce hydrator for all DTOs              |
| `src/Kubernetes/Helm/GroupAddons.php` | Kubernetes Helm worker add-ons         |
| `src/Terraform/Queues.php`        | Terraform queue name registry                 |

Do not edit these files manually; they will be overwritten on the next Composer run.

## License

The MIT License (MIT)

Copyright (c) 2026 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
