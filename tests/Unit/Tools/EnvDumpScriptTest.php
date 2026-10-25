<?php

declare(strict_types=1);

namespace App\Tests\Unit\Tools;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Скрипт `tools/env-dump.php`: собирает дамп env для demo-режима из цепочки `.env` → `.env.local` → `.env.prod` →
 * `.env.prod.local` (порядок Symfony `loadEnv`); `.env` обязателен, остальные `.local` — если есть.
 *
 * Каждая фикстура в `fixtures/env-dump-<name>/` — каталог dotenv-файлов. Скрипт запускается процессом с APP_ENV=prod,
 * наблюдаем код возврата и произведённый файл дампа — ни ядро Symfony, ни БД не нужны.
 */
final class EnvDumpScriptTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/env-dump-'.bin2hex(random_bytes(6));
        mkdir($this->workDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->workDir.'/{,.}*', \GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($this->workDir);
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{0: string, 1: string|null, 2: array<string, string>}>
     */
    public static function provideDumpContainsExactlyTheDemoChainCases(): iterable
    {
        // `.env.local` входит в цепочку до `.env.prod`: его собственные значения попадают в дамп, общие — перекрываются.
        yield 'chain with .env.local, without .env.prod.local' => [
            'env-dump-chain',
            null,
            ['APP_ENV' => 'prod', 'FIXTURE_BASE' => 'from_env', 'FIXTURE_LOCAL' => 'from_local', 'FIXTURE_VALUE' => 'from_prod'],
        ];

        // Повторный старт: прежний дамп перезаписывается, `.env.prod.local` перекрывает `.env.prod`.
        yield 'stale dump, .env.prod.local overrides .env.prod' => [
            'env-dump-overwrite',
            'stale-dump.php',
            ['APP_ENV' => 'prod', 'FIXTURE_BASE' => 'from_env', 'FIXTURE_VALUE' => 'from_prod_local'],
        ];
    }

    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('provideDumpContainsExactlyTheDemoChainCases')]
    public function testDumpContainsExactlyTheDemoChain(string $fixture, ?string $staleDump, array $expected): void
    {
        $dumpFile = $this->workDir.'/.env.local.php';
        if (null !== $staleDump) {
            copy(__DIR__.'/fixtures/'.$fixture.'/'.$staleDump, $dumpFile);
        }

        [$exitCode, $output] = $this->runEnvDump($fixture, $dumpFile);

        self::assertSame(0, $exitCode, $output);
        self::assertArraysAreIdenticalIgnoringOrder($expected, include $dumpFile);
    }

    public function testBrokenDotenvFailsWithNonZeroExitCode(): void
    {
        [$exitCode, $output] = $this->runEnvDump('env-dump-broken', $this->workDir.'/.env.local.php');

        self::assertNotSame(0, $exitCode, $output);
    }

    public function testMissingDotenvFailsWithNonZeroExitCode(): void
    {
        [$exitCode, $output] = $this->runEnvDump('env-dump-no-dotenv', $this->workDir.'/.env.local.php');

        self::assertNotSame(0, $exitCode, $output);
    }

    /**
     * @return array{0: int, 1: string} код возврата и объединённый stdout+stderr
     */
    private function runEnvDump(string $fixture, string $dumpFile): array
    {
        $command = 'APP_ENV=prod php '.escapeshellarg(\dirname(__DIR__, 3).'/tools/env-dump.php')
            .' '.escapeshellarg(__DIR__.'/fixtures/'.$fixture)
            .' '.escapeshellarg($dumpFile);

        $output = [];
        $exitCode = 0;
        exec($command.' 2>&1', $output, $exitCode);

        return [$exitCode, implode("\n", $output)];
    }
}
