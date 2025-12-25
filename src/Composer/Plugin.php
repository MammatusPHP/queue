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
use WyriHaximus\Composer\GenerativePluginTooling\Helper\Remove;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\TwigFile;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

use function file_exists;
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
        Remove::directoryContents($rootPath . '/src/Generated');

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

            TwigFile::render(
                $rootPath . '/etc/generated_templates/Consumer.php.twig',
                $rootPath . '/src/Generated/Consumer/' . $item->generateClassesClassNameSuffix . '.php',
                ['worker' => $item],
            );
            TwigFile::render(
                $rootPath . '/etc/generated_templates/WorkerFactory.php.twig',
                $rootPath . '/src/Generated/WorkerFactory/' . $item->generateClassesClassNameSuffix . '.php',
                ['worker' => $item],
            );
        }

        TwigFile::render(
            $rootPath . '/etc/generated_templates/AbstractList.php.twig',
            $rootPath . '/src/Generated/AbstractList.php',
            ['workers' => $workers],
        );

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileExists */
        if (file_exists($rootPath . '/tests/Composer/')) {
            TwigFile::render(
                $rootPath . '/etc/generated_templates/AbstractList.php.twig',
                $rootPath . '/tests/Composer/ExpectedAbstractList.php',
                ['workers' => $workers],
            );
        }

        TwigFile::render(
            $rootPath . '/etc/generated_templates/WorkQueueMap.php.twig',
            $rootPath . '/src/Generated/WorkQueueMap.php',
            ['items' => $map],
        );

        TwigFile::render(
            $rootPath . '/etc/generated_templates/GroupAddons.php.twig',
            $rootPath . '/src/Generated/Kubernetes/Helm/GroupAddons.php',
            ['workers' => $workers],
        );

        $hydratorGenerator = new ObjectMapperCodeGenerator();
        $code              = $hydratorGenerator->dump($dtos, Hydrator::class);
        file_put_contents($rootPath . '/src/Generated/Hydrator.php', $code);
    }
}
