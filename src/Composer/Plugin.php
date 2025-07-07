<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use Mammatus\Queue\Contracts\Worker;
use Mammatus\Queue\Generated\Hydrator;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Class\ImplementsInterface;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Class\IsInstantiable;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Package\ComposerJsonHasItemWithSpecificValue;
use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;
use WyriHaximus\Twig\SimpleTwig;

use function chmod;
use function file_get_contents;
use function file_put_contents;
use function md5;
use function serialize;

final class Plugin implements GenerativePlugin
{
    public static function name(): string
    {
        return 'mammatus/queue';
    }

    public static function log(LogStages $stage): string
    {
        return match ($stage) {
            LogStages::Init => 'Locating actions',
            LogStages::Error => 'An error occurred: %s',
            LogStages::Collected => 'Found %d action(s)',
            LogStages::Completion => 'Generated static abstract queue manager and queue list in %s second(s)',
        };
    }

    /** @inheritDoc */
    public function filters(): iterable
    {
        yield new ComposerJsonHasItemWithSpecificValue('mammatus.queue.has-workers', true);
        yield new ImplementsInterface(Worker::class);
        yield new IsInstantiable();
    }

    /** @inheritDoc */
    public function collectors(): iterable
    {
        yield new Collector();
    }

    public function compile(string $rootPath, ItemContract ...$items): void
    {
        $map     = [];
        $workers = [];
        $dtos    = [];
        foreach ($items as $item) {
            if (! ($item instanceof Item)) {
                continue;
            }

            $map[$item->dtoClass]           = [
                'dtoClass' => $item->dtoClass,
                'queue' => $item->consumer->queue,
            ];
            $workers[md5(serialize($item))] = $item;
            $dtos[$item->dtoClass]          = $item->dtoClass;
        }

        $classContentsList = SimpleTwig::render(
            file_get_contents( /** @phpstan-ignore-line */
                $rootPath . '/etc/generated_templates/AbstractList.php.twig',
            ),
            ['workers' => $workers],
        );
        $installPathList   = $rootPath . '/src/Generated/AbstractList.php';
        file_put_contents($installPathList, $classContentsList); /** @phpstan-ignore-line */
        chmod($installPathList, 0664);

        $classContentsList = SimpleTwig::render(
            file_get_contents( /** @phpstan-ignore-line */
                $rootPath . '/etc/generated_templates/WorkQueueMap.php.twig',
            ),
            ['items' => $map],
        );
        $installPathList   = $rootPath . '/src/Generated/WorkQueueMap.php';
        file_put_contents($installPathList, $classContentsList); /** @phpstan-ignore-line */
        chmod($installPathList, 0664);

        $hydratorGenerator = new ObjectMapperCodeGenerator();
        $code              = $hydratorGenerator->dump($dtos, Hydrator::class);
        file_put_contents($rootPath . '/src/Generated/Hydrator.php', $code);
    }
}
