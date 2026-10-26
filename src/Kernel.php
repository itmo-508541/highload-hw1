<?php

declare(strict_types=1);

namespace App;

use App\DependencyInjection\Compiler\CollectMetricsPass;
use OpenTelemetry\SDK\SdkAutoloader;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CollectMetricsPass());
    }
}
