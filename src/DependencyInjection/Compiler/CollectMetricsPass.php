<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Attribute\CollectMetrics;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CollectMetricsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $names = [];
        foreach (array_keys($container->findTaggedServiceIds('controller.service_arguments')) as $id) {
            $reflection = new \ReflectionClass(
                $container->getDefinition($id)->getClass()
            );
            foreach ($reflection->getMethods() as $method) {
                foreach ($method->getAttributes(CollectMetrics::class) as $attribute) {
                    $instance = $attribute->newInstance();

                    $names[] = $instance->name;
                }
            }
        }

        $names = array_values(array_unique($names));

        $container->setParameter('app.collect_metrics.names', $names);
    }
}
