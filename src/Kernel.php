<?php

declare(strict_types=1);

namespace App;

use OpenTelemetry\SDK\SdkAutoloader;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected bool $otelAutoloaded = false;

    #[\Override]
    public function boot(): void
    {
        if (!$this->booted && !$this->otelAutoloaded) {
            SdkAutoloader::autoload();
            $this->otelAutoloaded = true;
        }

        parent::boot();
    }
}
