<?php

declare(strict_types=1);

namespace App\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;

/**
 * Phing-задача `symfonyenv` (`tools/dotEnvPropertyTask.php`): зеркалит переменные,
 * загруженные через Symfony Dotenv, в свойства Phing.
 *
 * Каждая фикстура в `fixtures/<name>/` — отдельный мини-проект с `build.xml`;
 * phing вызывается без `-f`, то есть читает `build.xml` из рабочего каталога.
 * Наблюдаем код возврата процесса и его вывод — ни ядро Symfony, ни БД не нужны.
 */
final class DotEnvPropertyTaskTest extends TestCase
{
    public function testPrefixMirrorsDotEnvKeysIntoPhingProperties(): void
    {
        [$exitCode, $output] = $this->runPhing('prefix-mirror');

        self::assertSame(0, $exitCode, $output);
        self::assertStringContainsString('[prefix] myapp.FIXTURE_HOST=127.0.0.1', $output);
        self::assertStringContainsString('[prefix] myapp.FIXTURE_PORT=9000', $output);
    }

    public function testEnvLocalPhpOverridesEnv(): void
    {
        [$exitCode, $output] = $this->runPhing('dump-overrides-env');

        self::assertSame(0, $exitCode, $output);
        self::assertStringContainsString('[dump] dotenv.FIXTURE_VALUE=from_dump', $output);
        self::assertStringContainsString('[dump] dotenv.APP_ENV=dev', $output);
    }

    public function testAppEnvTestReadsEnvChain(): void
    {
        [$exitCode, $output] = $this->runPhing('test-env-overlay', ['-Dapp.env=test']);

        self::assertSame(0, $exitCode, $output);
        self::assertStringContainsString('[overlay] dotenv.APP_ENV=test', $output);
        self::assertStringContainsString('[overlay] dotenv.FIXTURE_VALUE=from_test', $output);
    }

    public function testMissingBootEnvFailsBuildWithClearError(): void
    {
        [$exitCode, $output] = $this->runPhing('missing-bootenv');

        self::assertNotSame(0, $exitCode, $output);
        self::assertStringContainsString('SYMFONY_DOTENV_VARS', $output);
    }

    /**
     * @param list<string> $args аргументы после исполняемого файла phing (например `-Dapp.env=test`)
     *
     * @return array{0: int, 1: string} код возврата и объединённый stdout+stderr
     */
    private function runPhing(string $fixture, array $args = []): array
    {
        $command = 'cd '.escapeshellarg(__DIR__.'/fixtures/'.$fixture).' && phing';
        foreach ($args as $arg) {
            $command .= ' '.escapeshellarg($arg);
        }

        $output = [];
        $exitCode = 0;
        exec($command.' 2>&1', $output, $exitCode);

        return [$exitCode, implode("\n", $output)];
    }
}
