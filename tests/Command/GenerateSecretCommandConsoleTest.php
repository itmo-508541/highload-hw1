<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Twig\Error\SyntaxError;

/**
 * {@see \App\Command\GenerateSecretCommand} через полное приложение — сценарии из бывшего {@code generate_secrets_console.feature}.
 */
final class GenerateSecretCommandConsoleTest extends KernelTestCase
{
    public function testMarkerFromEnvTestChain(): void
    {
        self::bootKernel(['environment' => 'test']);
        $out = $this->runConsoleCommand(__DIR__.'/fixtures/tools/console/marker.twig');
        self::assertSame('behat-console-marker', $out);
    }

    public function testMysqlPasswordTwigMatchesDotenvChain(): void
    {
        self::bootKernel(['environment' => 'test']);
        $out = $this->runConsoleCommand($this->projectRoot().'/secrets/mysql-password.twig');
        self::assertSame('root', $out);
    }

    public function testRedisRequirepassTwig(): void
    {
        self::bootKernel(['environment' => 'test']);
        $out = $this->runConsoleCommand(__DIR__.'/fixtures/tools/console/redis-requirepass.twig');
        self::assertSame("requirepass root\n", $out);
    }

    public function testMissingTemplateArgument(): void
    {
        self::bootKernel(['environment' => 'test']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments');
        $this->createConsoleTester()->execute(['command' => 'app:generate-secrets']);
    }

    public function testInvalidTwig(): void
    {
        self::bootKernel(['environment' => 'test']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unclosed');
        $this->runConsoleCommand(__DIR__.'/fixtures/tools/cases/invalid-twig/invalid.twig');
    }

    private function projectRoot(): string
    {
        return \dirname(__DIR__, 2);
    }

    private function runConsoleCommand(string $absoluteTemplatePath): string
    {
        $tester = $this->createConsoleTester();
        $exit = $tester->execute([
            'command' => 'app:generate-secrets',
            'template' => $absoluteTemplatePath,
        ]);
        self::assertSame(0, $exit, $tester->getDisplay());

        return $tester->getDisplay();
    }

    private function createConsoleTester(): CommandTester
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);
        $application = new Application($kernel);
        $application->setAutoExit(false);

        return new CommandTester($application->find('app:generate-secrets'));
    }
}
