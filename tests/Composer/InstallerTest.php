<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Mammatus\DevApp\Queue\Noop;
use Mammatus\Queue\Composer\CodeGenerator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function closedir;
use function dirname;
use function file_exists;
use function is_dir;
use function is_file;
use function readdir;
use function Safe\copy;
use function Safe\file_get_contents;
use function Safe\mkdir;
use function Safe\opendir;
use function touch;

use const DIRECTORY_SEPARATOR;

final class InstallerTest extends TestCase
{
    #[Test]
    public function getSubscribedEvents(): void
    {
        self::assertSame([ScriptEvents::PRE_AUTOLOAD_DUMP => 'findActions'], CodeGenerator::getSubscribedEvents());
    }

    #[Test]
    public function generate(): void
    {
        $composerConfig = $this->mockComposerConfig();
        $rootPackage    = $this->mockRootPackage();

        $io       = new IOMock();
        $composer = $this->mockComposer($io, $composerConfig, $rootPackage);

        $event = new Event(
            ScriptEvents::PRE_AUTOLOAD_DUMP,
            $composer,
            $io,
        );

        $installer = new CodeGenerator();

        // Test dead methods and make Infection happy
        $installer->activate($composer, $io);
        $installer->deactivate($composer, $io);
        $installer->uninstall($composer, $io);

        $this->recurseCopy(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, $this->getTmpDir());

        $sneakyFile = $this->getTmpDir() . 'src' . DIRECTORY_SEPARATOR . 'Generated' . DIRECTORY_SEPARATOR . 'sneaky.file';
        touch($sneakyFile);

        $fileNameWorkerFactory = $this->getTmpDir() . 'src/Generated/WorkerFactory/MammatusDevAppQueueNoopViaPerformForNoopWithMammatusDevAppQueueEmptyMessage.php';

        self::assertFileExists($sneakyFile);

        // Do the actual generating
        CodeGenerator::findActions($event);

        self::assertFileDoesNotExist($sneakyFile);
        self::assertFileDoesNotExist($this->getTmpDir() . 'src/Generated/WorkerFactory/MammatusDevAppQueueOHellNoViaConstructForNoopWithMammatusDevAppQueueEmptyMessageAsNoop3.php');

        $output = $io->output();

        self::assertStringContainsString('<info>mammatus/queue:</info> Locating actions', $output);
        self::assertStringContainsString('<info>mammatus/queue:</info> Generated static abstract queue manager and queue list in ', $output);
        self::assertStringContainsString('<info>mammatus/queue:</info> Found 5 action(s)', $output);

        $fileContentsWorkerFactory = file_get_contents($fileNameWorkerFactory);

        self::assertStringContainsStringIgnoringCase('/** @see \\' . Noop::class, $fileContentsWorkerFactory);
        self::assertStringContainsStringIgnoringCase('EmptyMessage::class,', $fileContentsWorkerFactory);
        self::assertStringContainsStringIgnoringCase('json_decode(\'[]\', true),', $fileContentsWorkerFactory);
    }

    private function mockComposerConfig(): Config
    {
        $composerConfig = new Config();
        $composerConfig->merge([
            'config' => [
                'vendor-dir' => $this->getTmpDir() . 'vendor' . DIRECTORY_SEPARATOR,
            ],
        ]);

        return $composerConfig;
    }

    private function mockRootPackage(): RootPackage
    {
        $rootPackage = new RootPackage('mammatus/queue', 'dev-master', 'dev-master');
        $rootPackage->setExtra([
            'mammatus' => [
                'queue' => ['has-workers' => true],
            ],
        ]);
        $rootPackage->setAutoload([
            'psr-4' => [
                'Mammatus\\DevApp\\Queue\\' => 'etc/dev-app',
                'Mammatus\\Queue\\' => 'src',
            ],
        ]);

        return $rootPackage;
    }

    private function mockComposer(NullIO $io, Config $composerConfig, RootPackage $rootPackage): Composer
    {
        $repository = Mockery::mock(InstalledRepositoryInterface::class);
        $repository->allows()->getCanonicalPackages()->andReturn([]);
        $repositoryManager = new RepositoryManager($io, $composerConfig, Factory::createHttpDownloader($io, $composerConfig));
        $repositoryManager->setLocalRepository($repository);
        $composer = new Composer();
        $composer->setConfig($composerConfig);
        $composer->setRepositoryManager($repositoryManager);
        $composer->setPackage($rootPackage);

        return $composer;
    }

    private function recurseCopy(string $src, string $dst): void
    {
        $dir = opendir($src);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileExists */
        if (! file_exists($dst)) {
            mkdir($dst);
        }

        while (( $file = readdir($dir)) !== false) {
            if (( $file === '.' ) || ( $file === '..' )) {
                continue;
            }

            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.isDir */
            if (is_dir($src . $file)) {
                $this->recurseCopy($src . $file . DIRECTORY_SEPARATOR, $dst . $file . DIRECTORY_SEPARATOR);
            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.isFile */
            } elseif (is_file($src . $file)) {
                copy($src . $file, $dst . $file);
            }
        }

        closedir($dir);
    }
}
