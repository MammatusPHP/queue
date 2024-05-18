<?php

declare(strict_types=1);

namespace Mammatus\Queue\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Illuminate\Support\Collection;
use Mammatus\Kubernetes\Attributes\SplitOut;
use Mammatus\Queue\Attributes\Consumer;
use Mammatus\Queue\Contracts\Worker;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Exception\InvalidPrefixMapping;
use RuntimeException;

use function array_key_exists;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function is_array;
use function is_string;
use function microtime;
use function round;
use function rtrim;
use function Safe\chmod;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\spl_autoload_register;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function WyriHaximus\getIn;
use function WyriHaximus\listClassesInDirectories;
use function WyriHaximus\Twig\render;

use const DIRECTORY_SEPARATOR;

final class Installer implements PluginInterface, EventSubscriberInterface
{
    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [ScriptEvents::PRE_AUTOLOAD_DUMP => 'findActions'];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    /**
     * Called before every dump autoload, generates a fresh PHP class.
     */
    public static function findActions(Event $event): void
    {
        $start     = microtime(true);
        $io        = $event->getIO();
        $composer  = $event->getComposer();
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        if (! is_string($vendorDir)) {
            throw new RuntimeException('Vendor dir must be a string');
        }

        // Composer is bugged and doesn't handle root package autoloading properly yet
        if (array_key_exists('psr-4', $composer->getPackage()->getAutoload())) {
            foreach ($composer->getPackage()->getAutoload()['psr-4'] as $ns => $p) {
                /** @phpstan-ignore-next-line */
                $p = dirname($vendorDir) . '/' . $p;
                spl_autoload_register(static function ($class) use ($ns, $p): void {
                    if (strpos($class, $ns) !== 0) {
                        return;
                    }

                    $fileName = $p . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($ns))) . '.php';
                    /** @phpstan-ignore-next-line */
                    if (! file_exists($fileName)) {
                        return;
                    }

                    include $fileName;
                });
            }
        }

        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/wyrihaximus/list-classes-in-directory/src/functions_include.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/wyrihaximus/string-get-in/src/functions_include.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/wyrihaximus/constants/src/Numeric/constants_include.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/igorw/get-in/src/get_in.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/jetbrains/phpstorm-stubs/PhpStormStubsMap.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/thecodingmachine/safe/generated/filesystem.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/thecodingmachine/safe/generated/strings.php';
        /** @psalm-suppress UnresolvableInclude */
        require_once $composer->getConfig()->get('vendor-dir') . '/wyrihaximus/simple-twig/src/functions_include.php';
        /** @psalm-suppress UnresolvableInclude */

        $io->write('<info>mammatus/queue:</info> Locating actions');

        $actions = self::findAllActions($composer, $io);

        $io->write(sprintf('<info>mammatus/queue:</info> Found %s action(s)', count($actions)));

        $classContentsList = render(
            file_get_contents(
                self::locateRootPackageInstallPath($composer->getConfig(), $composer->getPackage()) . '/etc/generated_templates/AbstractList_.php.twig',
            ),
            ['workers' => $actions],
        );

        $installPathList = self::locateRootPackageInstallPath($composer->getConfig(), $composer->getPackage())
            . '/src/Generated/AbstractList_.php';

        file_put_contents($installPathList, $classContentsList);
        chmod($installPathList, 0664);

        $io->write(sprintf(
            '<info>mammatus/queue:</info> Generated static abstract queue manager and queue list in %s second(s)',
            round(microtime(true) - $start, 2),
        ));
    }

    /**
     * Find the location where to put the generate PHP class in.
     */
    private static function locateRootPackageInstallPath(
        Config $composerConfig,
        RootPackageInterface $rootPackage,
    ): string {
        $vendorDir = $composerConfig->get('vendor-dir');
        if (! is_string($vendorDir)) {
            throw new RuntimeException('Vendor dir must be a string');
        }

        // You're on your own
        if ($rootPackage->getName() === 'mammatus/queue') {
            return dirname($vendorDir);
        }

        return $vendorDir . '/mammatus/queue';
    }

    /** @return array<mixed> */
    private static function findAllActions(Composer $composer, IOInterface $io): array
    {
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        if (! is_string($vendorDir)) {
            throw new RuntimeException('Vendor dir must be a string');
        }

        retry:
        try {
            $classReflector = new DefaultReflector(
                (new MakeLocatorForComposerJsonAndInstalledJson())(dirname($vendorDir), (new BetterReflection())->astLocator()),
            );
        } catch (InvalidPrefixMapping $invalidPrefixMapping) {
            mkdir(explode('" is not a', explode('" for prefix "', $invalidPrefixMapping->getMessage())[1])[0]);
            goto retry;
        }

        $packages   = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packages[] = $composer->getPackage();

        return (new Collection($packages))->filter(static function (PackageInterface $package): bool {
            return count($package->getAutoload()) > 0;
        })->filter(static function (PackageInterface $package): bool {
            /** @phpstan-ignore-next-line */
            return getIn($package->getExtra(), 'mammatus.queue.has-workers', false);
        })->filter(static function (PackageInterface $package): bool {
            return array_key_exists('classmap', $package->getAutoload()) || array_key_exists('psr-4', $package->getAutoload());
        })->flatMap(static function (PackageInterface $package) use ($vendorDir): array {
            $packageName = $package->getName();
            $autoload    = $package->getAutoload();
            $paths       = [];
            foreach (['classmap', 'psr-4'] as $item) {
                if (! array_key_exists($item, $autoload)) {
                    continue;
                }

                foreach ($autoload[$item] as $path) {
                    if (is_string($path)) {
                        if ($package instanceof RootPackageInterface) {
                            $paths[] = dirname($vendorDir) . DIRECTORY_SEPARATOR . $path;
                        } else {
                            $paths[] = $vendorDir . DIRECTORY_SEPARATOR . $packageName . DIRECTORY_SEPARATOR . $path;
                        }
                    }

                    if (! is_array($path)) {
                        continue;
                    }

                    foreach ($path as $p) {
                        if ($package instanceof RootPackageInterface) {
                            $paths[] = dirname($vendorDir) . DIRECTORY_SEPARATOR . $p;
                        } else {
                            $paths[] = $vendorDir . DIRECTORY_SEPARATOR . $packageName . DIRECTORY_SEPARATOR . $p;
                        }
                    }
                }
            }

            return $paths;
        })->map(static function (string $path): string {
            return rtrim($path, '/');
        })->filter(static function (string $path): bool {
            /** @phpstan-ignore-next-line */
            return file_exists($path);
        })->flatMap(static function (string $path): array {
            return [...listClassesInDirectories($path)];
            /** @phpstan-ignore-next-line */
        })->flatMap(static function (string $class) use ($classReflector, $io): array {
            try {
                /** @psalm-suppress PossiblyUndefinedVariable */
                return [
                    (static function (ReflectionClass $reflectionClass): ReflectionClass {
                        $reflectionClass->getInterfaces();
                        $reflectionClass->getMethods();

                        return $reflectionClass;
                    })($classReflector->reflectClass($class)),
                ];
            } catch (IdentifierNotFound $identifierNotFound) {
                $io->write(sprintf(
                    '<info>mammatus/queue:</info> Error while reflecting "<fg=cyan>%s</>": <fg=yellow>%s</>',
                    $class,
                    $identifierNotFound->getMessage(),
                ));
            }

            return [];
        })->filter(static function (ReflectionClass $class): bool {
            return $class->isInstantiable();
        })->filter(static function (ReflectionClass $class): bool {
            return $class->implementsInterface(Worker::class);
        })->flatMap(static function (ReflectionClass $class): array {
            $attributes = [];
            foreach ((new \ReflectionClass($class->getName()))->getAttributes() as $attributeReflection) {
                $attribute                     = $attributeReflection->newInstance();
                $attributes[$attribute::class] = $attribute;
            }

            return [
                [
                    'class' => $class->getName(),
                    'attributes' => $attributes,
                ],
            ];
        })->filter(static function (array $classNattributes): bool {
            return array_key_exists(Consumer::class, $classNattributes['attributes']);
        })->flatMap(static function (array $classNattributes): array {
            return [
                [
                    'class' => $classNattributes['class'],
                    'consumer' => $classNattributes['attributes'][Consumer::class],
                    'split_out' => array_key_exists(SplitOut::class, $classNattributes['attributes']),
                ],
            ];
        })->toArray();
    }
}
