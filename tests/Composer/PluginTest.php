<?php

declare(strict_types=1);

namespace Mammatus\Tests\Queue\Composer;

use Mammatus\Queue\Composer\Plugin;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\TestUtilities\TestCase;

use function basename;
use function copy;
use function dirname;
use function glob;
use function mkdir;

use const DIRECTORY_SEPARATOR;

final class PluginTest extends TestCase
{
    #[Test]
    public function compileSkipsNonItemContracts(): void
    {
        $projectRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $tmpDir      = $this->getTmpDir();
        $templates   = $tmpDir . 'etc' . DIRECTORY_SEPARATOR . 'generated_templates' . DIRECTORY_SEPARATOR;

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.mkdir */
        mkdir($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer', 0777, true);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.mkdir */
        mkdir($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Kubernetes', 0777, true);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.mkdir */
        mkdir($templates, 0777, true);

        $templateFiles = glob($projectRoot . 'etc' . DIRECTORY_SEPARATOR . 'generated_templates' . DIRECTORY_SEPARATOR . '*.twig');
        self::assertIsArray($templateFiles);
        self::assertNotCount(0, $templateFiles);

        foreach ($templateFiles as $template) {
            copy($template, $templates . basename($template));
        }

        new Plugin()->compile($tmpDir, new class () implements ItemContract {
        });

        self::assertFileDoesNotExist($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Consumer' . DIRECTORY_SEPARATOR . 'Anything.php');
        self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Producer.php');
        self::assertFileExists($tmpDir . 'src' . DIRECTORY_SEPARATOR . 'Hydrator.php');
    }
}
