<?php

declare(strict_types=1);

namespace App\Traits;

use App\Service\LoaderConfig;

trait LoaderConfigTrait
{
    protected LoaderConfig $loaderConfig;

    /**
     * @required
     */
    public function setLoaderConfig(LoaderConfig $loaderConfig): void
    {
        $this->loaderConfig = $loaderConfig;
    }
}
