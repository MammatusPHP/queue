<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use Mammatus\Queue\Contracts\Worker;
use Mammatus\Queue\Hydrator;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Class\ImplementsInterface;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Class\IsInstantiable;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Package\ComposerJsonHasItemWithSpecificValue;
use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\File;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\Remove;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\TwigFile;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

use function md5;
use function serialize;

use const PHP_EOL;

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
        Remove::directoryContents($rootPath . '/src/Consumer');
        Remove::directoryContents($rootPath . '/src/Kubernetes');
        Remove::file($rootPath . '/src/Hydrator.php');
        Remove::file($rootPath . '/src/Producer.php');

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
                $rootPath . '/src/Consumer/' . $item->generateClassesClassNameSuffix . '.php',
                ['worker' => $item],
            );
            TwigFile::render(
                $rootPath . '/etc/generated_templates/WorkerFactory.php.twig',
                $rootPath . '/src/Consumer/WorkerFactory/' . $item->generateClassesClassNameSuffix . '.php',
                ['worker' => $item],
            );
        }

        TwigFile::render(
            $rootPath . '/etc/generated_templates/Producer.php.twig',
            $rootPath . '/src/Producer.php',
            ['items' => $map],
        );

        TwigFile::render(
            $rootPath . '/etc/generated_templates/GroupAddons.php.twig',
            $rootPath . '/src/Kubernetes/Helm/GroupAddons.php',
            ['workers' => $workers],
        );

        File::write(
            $rootPath . '/src/Hydrator.php',
            new ObjectMapperCodeGenerator()->dump($dtos, Hydrator::class) . PHP_EOL,
        );
    }
}
