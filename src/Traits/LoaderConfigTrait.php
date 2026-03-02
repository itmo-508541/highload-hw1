<?php

declare(strict_types=1);

namespace App\Traits;

use App\Service\LoaderConfig;
use Symfony\Contracts\Service\Attribute\Required;

trait LoaderConfigTrait
{
    protected LoaderConfig $loaderConfig;

    #[Required]
    public function setLoaderConfig(LoaderConfig $loaderConfig): void
    {
        $this->loaderConfig = $loaderConfig;
    }
}
