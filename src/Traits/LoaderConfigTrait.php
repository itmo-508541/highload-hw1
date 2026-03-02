<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Contracts\Service\Attribute\Required;
use App\Service\LoaderConfig;

trait LoaderConfigTrait
{
    protected LoaderConfig $loaderConfig;

    #[Required]
    public function setLoaderConfig(LoaderConfig $loaderConfig): void
    {
        $this->loaderConfig = $loaderConfig;
    }
}
