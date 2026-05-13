<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\GenerateSecretCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\FormatException;
use Twig\Error\SyntaxError;

/**
 * {@see GenerateSecretCommand} после {@see Dotenv::bootEnv()} — сценарии из бывшего {@code generate_secret.feature}.
 */
final class GenerateSecretCommandBootEnvTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $serverSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverSnapshot = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverSnapshot;
        parent::tearDown();
    }

    public function testPasswordOverrideFromEnvTest(): void
    {
        $this->bootProjectFixture('tests/tools/generate-secret/cases/password-override/.env');
        $out = $this->runCommand('tests/tools/generate-secret/cases/password-override/mysql-password.twig');
        self::assertSame('asdf', $out);
    }

    public function testPasswordFromBaseEnv(): void
    {
        $this->bootProjectFixture('tests/tools/generate-secret/cases/password-override/.env');
        $out = $this->runCommand('tests/tools/generate-secret/cases/password-override/redis.conf.twig');
        self::assertSame("requirepass zxcv\n", $out);
    }

    public function testLayeredEnvTestOverridesAndAdds(): void
    {
        $this->bootProjectFixture('tests/tools/generate-secret/cases/layered/.env');
        $out = $this->runCommand('tests/tools/generate-secret/cases/layered/layered.twig');
        self::assertSame("1 new 3\n", $out);
    }

    public function testMissingTemplateArgument(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments');
        $tester = new CommandTester(new GenerateSecretCommand());
        $tester->execute([]);
    }

    public function testBadSyntaxEnvThrowsBeforeCommand(): void
    {
        $this->expectException(FormatException::class);
        $this->bootProjectFixture('tests/tools/generate-secret/cases/bad-syntax/.env');
    }

    public function testInvalidTwigSyntaxError(): void
    {
        $this->bootProjectFixture('tests/tools/generate-secret/cases/invalid-twig/.env');
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unclosed');
        $this->runCommand('tests/tools/generate-secret/cases/invalid-twig/invalid.twig');
    }

    private function bootProjectFixture(string $dotenvRelativeToProjectRoot): void
    {
        $path = \dirname(__DIR__, 2).'/'.$dotenvRelativeToProjectRoot;
        self::assertFileIsReadable($path);
        new Dotenv()->bootEnv($path, overrideExistingVars: true);
    }

    private function runCommand(string $templateRelativeToProjectRoot): string
    {
        $path = \dirname(__DIR__, 2).'/'.$templateRelativeToProjectRoot;
        $command = new GenerateSecretCommand();
        $tester = new CommandTester($command);
        $exit = $tester->execute(['template' => $path]);
        self::assertSame(0, $exit, $tester->getDisplay());

        return $tester->getDisplay();
    }
}
