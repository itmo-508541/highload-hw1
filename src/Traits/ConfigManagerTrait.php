<?php

declare(strict_types=1);

namespace App\Traits;

use App\Manager\ConfigManager;
use Symfony\Contracts\Service\Attribute\Required;

trait ConfigManagerTrait
{
    protected ConfigManager $configManager;

    #[Required]
    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }
}
