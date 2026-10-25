<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Attribute\CollectMetrics;
use OpenTelemetry\API\Metrics\MeterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CollectMetricsListener implements EventSubscriberInterface
{
    private const string STATUS_SUCCESS = 'ok';

    private const string STATUS_FAILURE = 'error';

    private ?string $controller = null;

    private ?float $startTime = null;

    public function __construct(
        private readonly MeterInterface $meter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -100],
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->controller = null;
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
                $this->controller = $arguments['name'] ?? $arguments[0] ?? null;
            }
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() && null !== $this->controller) {
            $this->recordLatency($this->controller, self::STATUS_SUCCESS);

            $this->controller = null;
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->isMainRequest() && null !== $this->controller) {
            $this->recordLatency($this->controller, self::STATUS_FAILURE);

            $this->controller = null;
        }
    }

    private function recordLatency(string $controller, string $status): void
    {
        $name = $this->getMetricName($controller, 'latency');
        $value = (microtime(true) - $this->startTime) * 1000;

        $latency = $this->meter->createHistogram($name, 'ms');
        $latency->record((int) $value, ['status' => $status]);
    }

    private function getMetricName(string $controller, string $name): string
    {
        return sprintf('controller_%s_%s', $controller, $name);
    }
}
