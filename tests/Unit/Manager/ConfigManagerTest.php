<?php

declare(strict_types=1);

namespace App\Tests\Unit\Manager;

use App\Manager\ConfigManager;
use App\Repository\ConfigRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ConfigManagerTest extends TestCase
{
    public function testGetValueDelegatesToRepository(): void
    {
        $params = ['id' => 1];
        $suffix = 'key.suffix';
        $default = 'fallback';

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('getValue')
            ->with(self::equalTo($params), $suffix, $default)
            ->willReturn('stored');

        $manager = new ConfigManager();
        $manager->setConfigRepository($repository);

        self::assertSame('stored', $manager->getValue($params, $suffix, $default));
    }

    public function testSetValueDelegatesToRepository(): void
    {
        $params = ['scope' => 'app'];
        $suffix = 'mail.from';
        $value = 'noreply@example.test';

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('setValue')
            ->with(self::equalTo($params), $suffix, $value);

        $manager = new ConfigManager();
        $manager->setConfigRepository($repository);

        $manager->setValue($params, $suffix, $value);
    }

    public function testGetTemplatedValueWhenTemplateIsNullReturnsNull(): void
    {
        $params = ['id' => 2];
        $suffix = 'template.body';

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('getValue')
            ->with(self::equalTo($params), $suffix, null)
            ->willReturn(null);

        $manager = new ConfigManager();
        $manager->setConfigRepository($repository);

        self::assertNull($manager->getTemplatedValue($params, $suffix, ['name' => 'ignored']));
    }

    public function testGetTemplatedValueWithoutPlaceholdersReturnsUnchangedString(): void
    {
        $params = [];
        $suffix = 'plain';

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('getValue')
            ->with(self::equalTo($params), $suffix, null)
            ->willReturn('no placeholders here');

        $manager = new ConfigManager();
        $manager->setConfigRepository($repository);

        self::assertSame(
            'no placeholders here',
            $manager->getTemplatedValue($params, $suffix, ['unused' => 'x'])
        );
    }

    public function testGetTemplatedValueReplacesSinglePlaceholder(): void
    {
        $params = ['tenant' => 'a'];
        $suffix = 'greeting';

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('getValue')
            ->with(self::equalTo($params), $suffix, null)
            ->willReturn('Hello {{name}}!');

        $manager = new ConfigManager();
        $manager->setConfigRepository($repository);

        self::assertSame(
            'Hello World!',
            $manager->getTemplatedValue($params, $suffix, ['name' => 'World'])
        );
    }

    public function testGetTemplatedValueReplacesMultiplePlaceholders(): void
    {
        $params = ['x' => 1];
        $suffix = 'multi';

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('getValue')
            ->with(self::equalTo($params), $suffix, null)
            ->willReturn('{{a}}-{{b}}');

        $manager = new ConfigManager();
        $manager->setConfigRepository($repository);

        self::assertSame(
            '1-2',
            $manager->getTemplatedValue($params, $suffix, ['a' => '1', 'b' => '2'])
        );
    }

    public function testGetTemplatedValueWithEmptyTemplateReturnsEmptyString(): void
    {
        $params = [];
        $suffix = 'empty';

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('getValue')
            ->with(self::equalTo($params), $suffix, null)
            ->willReturn('');

        $manager = new ConfigManager();
        $manager->setConfigRepository($repository);

        self::assertSame('', $manager->getTemplatedValue($params, $suffix, ['k' => 'v']));
    }
}
