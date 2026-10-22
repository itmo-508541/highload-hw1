<?php

declare(strict_types=1);

namespace App\Tests\Unit\Traits;

use App\Manager\ConfigManager;
use App\Traits\ConfigManagerTrait;

final class ConfigManagerTraitTest extends RequiredSetterTestCase
{
    protected function setUp(): void
    {
        $this->object = new class {
            use ConfigManagerTrait;
        };
        $this->setterName = 'setConfigManager';
        $this->propertyClass = ConfigManager::class;
        $this->propertyName = 'configManager';
    }
}
