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
use Mammatus\Queue\Composer\Installer;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function closedir;
use function dirname;
use function file_exists;
use function in_array;
use function is_dir;
use function is_file;
use function readdir;
use function Safe\copy;
use function Safe\file_get_contents;
use function Safe\fileperms;
use function Safe\mkdir;
use function Safe\opendir;
use function Safe\unlink;
use function sprintf;
use function substr;

use const DIRECTORY_SEPARATOR;

final class InstallerTest extends TestCase
{
    #[Test]
    public function getSubscribedEvents(): void
    {
        self::assertSame([ScriptEvents::PRE_AUTOLOAD_DUMP => 'findActions'], Installer::getSubscribedEvents());
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

        $installer = new Installer();

        // Test dead methods and make Infection happy
        $installer->activate($composer, $io);
        $installer->deactivate($composer, $io);
        $installer->uninstall($composer, $io);

        $this->recurseCopy(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, $this->getTmpDir());

        $fileNameList = $this->getTmpDir() . 'src/Generated/AbstractList.php';
        if (file_exists($fileNameList)) { /** @phpstan-ignore-line */
            unlink($fileNameList);
        }

        self::assertFileDoesNotExist($fileNameList);

        // Do the actual generating
        Installer::findActions($event);

        $output = $io->output();

        self::assertStringContainsString('<info>mammatus/queue:</info> Locating actions', $output);
        self::assertStringContainsString('<info>mammatus/queue:</info> Generated static abstract queue manager and queue list in ', $output);
        self::assertStringContainsString('<info>mammatus/queue:</info> Found 0 action(s)', $output);

        self::assertFileExists($fileNameList);
        self::assertTrue(in_array(
            substr(sprintf('%o', fileperms($fileNameList)), -4),
            [
                '0664',
                '0666',
            ],
            true,
        ));
        $fileContentsList = file_get_contents($fileNameList);
        self::assertStringContainsStringIgnoringCase(' * @see \Mammatus\Queue\BuildIn\Noop', $fileContentsList);
        self::assertStringContainsStringIgnoringCase('yield \'internal-Mammatus-Queue-BuildIn-Noop-f7132ca9f5e47a9f9426a1ff6b0f7a39\' => WorkerFactory', $fileContentsList);
        self::assertStringContainsStringIgnoringCase('addOns: \json_decode(\'[]\', true), /** @ p h pstan-ignore-line */', $fileContentsList);
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
                'queue' => ['has-actions' => true],
            ],
        ]);
        $rootPackage->setAutoload([
            'psr-4' => ['Mammatus\\Queue\\' => 'src'],
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
        if (! file_exists($dst)) { /** @phpstan-ignore-line */
            mkdir($dst);
        }

        while (( $file = readdir($dir)) !== false) {
            if (( $file === '.' ) || ( $file === '..' )) {
                continue;
            }

            if (is_dir($src . $file)) { /** @phpstan-ignore-line */
                $this->recurseCopy($src . $file . DIRECTORY_SEPARATOR, $dst . $file . DIRECTORY_SEPARATOR);
            } elseif (is_file($src . $file)) { /** @phpstan-ignore-line */
//                echo $src . $file, ' => ', $dst . $file, PHP_EOL;
                copy($src . $file, $dst . $file);
            }
        }

        closedir($dir);
    }
}
