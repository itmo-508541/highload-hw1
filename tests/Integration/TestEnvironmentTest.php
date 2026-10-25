<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Ядро PHPUnit работает в окружении `test`, которое задаёт `phpunit.dist.xml`.
 *
 * Краснеет, если в корне проекта появился `.env.local.php`: почему — docs/adr/0005-env-dump-only-in-demo.md.
 */
final class TestEnvironmentTest extends KernelTestCase
{
    public function testKernelRunsInTestEnvironment(): void
    {
        self::bootKernel();

        self::assertSame('test', self::$kernel?->getEnvironment());
    }
}
