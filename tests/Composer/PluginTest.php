<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Mammatus\DevApp\Queue\Bar;
use Mammatus\DevApp\Queue\EmptyMessage;
use Mammatus\DevApp\Queue\Noop;
use Mammatus\Queue\Composer\Collector;
use Mammatus\Queue\Composer\Item;
use Mammatus\Queue\Composer\Plugin;
use Mammatus\Tests\Queue\Composer\Fixture\SplitOutWorker;
use PHPUnit\Framework\Attributes\Test;
use Roave\BetterReflection\Reflection\ReflectionClass;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\TestUtilities\TestCase;

use function basename;
use function copy;
use function dirname;
use function file_get_contents;
use function glob;
use function mkdir;
use function preg_match;
use function strpos;

use const DIRECTORY_SEPARATOR;

final class PluginTest extends TestCase
{
    #[Test]
    public function compileSkipsNonItemContracts(): void
    {
        $tmpDir = $this->setUpCompileProject();

        new Plugin()->compile($tmpDir, new class () implements ItemContract {
        });

        self::assertFileDoesNotExist($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer' . DIRECTORY_SEPARATOR . 'Anything.php');
        self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Producer.php');
        self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Hydrator.php');
    }

    #[Test]
    public function compileGeneratesFilesForCollectedItems(): void
    {
        $tmpDir = $this->setUpCompileProject();
        $items  = [
            ...new Collector()->collect(ReflectionClass::createFromName(SplitOutWorker::class)),
            ...new Collector()->collect(ReflectionClass::createFromName(Noop::class)),
        ];

        self::assertNotCount(0, $items);

        new Plugin()->compile($tmpDir, new class () implements ItemContract {
        }, ...$items);

        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer' . DIRECTORY_SEPARATOR . $item->generateClassesClassNameSuffix . '.php');
            self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer' . DIRECTORY_SEPARATOR . 'WorkerFactory' . DIRECTORY_SEPARATOR . $item->generateClassesClassNameSuffix . '.php');
        }

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
        $producerContents = file_get_contents($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Producer.php');
        self::assertIsString($producerContents);

        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertStringContainsString($item->dtoClass . '::class => \'' . $item->consumer->queue . '\'', $producerContents);
        }

        self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Kubernetes' . DIRECTORY_SEPARATOR . 'Helm' . DIRECTORY_SEPARATOR . 'GroupAddons.php');
        self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Terraform' . DIRECTORY_SEPARATOR . 'Queues.php');

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
        $terraformContents = file_get_contents($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Terraform' . DIRECTORY_SEPARATOR . 'Queues.php');
        self::assertIsString($terraformContents);
        self::assertTrue(preg_match("/'noop'/", $terraformContents) === 1, 'Terraform queue list should contain noop queue');
    }

    #[Test]
    public function compileContinuesAfterNonItemContracts(): void
    {
        $tmpDir = $this->setUpCompileProject();
        $items  = [...new Collector()->collect(ReflectionClass::createFromName(Bar::class))];

        self::assertCount(2, $items);

        new Plugin()->compile($tmpDir, new class () implements ItemContract {
        }, new class () implements ItemContract {
        }, ...$items);

        foreach ($items as $item) {
            self::assertInstanceOf(Item::class, $item);
            self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer' . DIRECTORY_SEPARATOR . $item->generateClassesClassNameSuffix . '.php');
            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
            $consumerContents = file_get_contents($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer' . DIRECTORY_SEPARATOR . $item->generateClassesClassNameSuffix . '.php');
            self::assertIsString($consumerContents);
            self::assertStringContainsString($item->groupName, $consumerContents);
        }
    }

    #[Test]
    public function compileGeneratesSortedQueues(): void
    {
        $tmpDir = $this->setUpCompileProject();
        $items  = [
            ...new Collector()->collect(ReflectionClass::createFromName(SplitOutWorker::class)),
            ...new Collector()->collect(ReflectionClass::createFromName(Noop::class)),
        ];

        new Plugin()->compile($tmpDir, ...$items);

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
        $terraformContents = file_get_contents($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Terraform' . DIRECTORY_SEPARATOR . 'Queues.php');
        self::assertIsString($terraformContents);

        $noopPos = strpos($terraformContents, "'noop'");
        self::assertIsInt($noopPos);

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
        $producerContents = file_get_contents($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Producer.php');
        self::assertIsString($producerContents);

        self::assertStringContainsString(EmptyMessage::class . '::class', $producerContents);
    }

    #[Test]
    public function compileGeneratesHydratorWithExpectedDtos(): void
    {
        $tmpDir = $this->setUpCompileProject();
        $items  = [...new Collector()->collect(ReflectionClass::createFromName(SplitOutWorker::class))];

        new Plugin()->compile($tmpDir, ...$items);

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
        $hydratorContents = file_get_contents($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Hydrator.php');
        self::assertIsString($hydratorContents);
        self::assertStringContainsString('EmptyMessage', $hydratorContents);
    }

    private function setUpCompileProject(): string
    {
        $projectRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $tmpDir      = $this->getTmpDir();
        $templates   = $tmpDir . 'etc' . DIRECTORY_SEPARATOR . 'generated_templates' . DIRECTORY_SEPARATOR;

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.mkdir */
        mkdir($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer' . DIRECTORY_SEPARATOR . 'WorkerFactory', 0777, true);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.mkdir */
        mkdir($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Kubernetes' . DIRECTORY_SEPARATOR . 'Helm', 0777, true);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.mkdir */
        mkdir($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Terraform', 0777, true);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.mkdir */
        mkdir($templates, 0777, true);

        $templateFiles = glob($projectRoot . 'etc' . DIRECTORY_SEPARATOR . 'generated_templates' . DIRECTORY_SEPARATOR . '*.twig');
        self::assertIsArray($templateFiles);
        self::assertNotCount(0, $templateFiles);

        foreach ($templateFiles as $template) {
            copy($template, $templates . basename($template));
        }

        return $tmpDir;
    }
}
