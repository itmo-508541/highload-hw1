<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Attribute\CollectMetrics;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CollectMetricsListener implements EventSubscriberInterface
{
    private array $attributes = [];

    private ?float $startTime = null;

    public function __construct(
        private readonly HistogramInterface $latency,
        private readonly CounterInterface $throughput,
        private readonly array $defaultAttributes = [],
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->attributes = $this->defaultAttributes;
            $this->startTime = microtime(true);
        }
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ($event->isMainRequest()) {
            $controller = $event->getControllerReflector();
            $collect = $controller->getAttributes(CollectMetrics::class)[0] ?? null;
            if (null !== $collect) {
                $arguments = $collect->getArguments();
                $name = $arguments['name'] ?? $arguments[0] ?? null;
                if (null !== $name) {
                    $this->attributes['controller'] = $name;
                }
            }
        }
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($event->isMainRequest()) {
            $statusCode = $event->getResponse()->getStatusCode();
            $this->attributes['status_code'] = $statusCode;

            $this->latency->record((microtime(true) - $this->startTime) * 1000, $this->attributes);
            $this->throughput->add(1, $this->attributes);
            // if ($statusCode >= 400) {
            //    $this->errors->add(1, $this->attributes);
            // }
        }
    }
}
