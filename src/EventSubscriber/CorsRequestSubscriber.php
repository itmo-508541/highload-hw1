<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @see https://qna.habr.com/q/684514
 */
class CorsRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onRequestEvent', 5000],
            ResponseEvent::class => 'onResponseEvent',
        ];
    }

    public function onRequestEvent(RequestEvent $event): void
    {
        if ($this->checkRequest($event->getRequest())) {
            $response = new Response(null);
            $event->setResponse($response);
        }
    }

    public function onResponseEvent(ResponseEvent $event): void
    {
        $headers = $event->getResponse()->headers;
        $headers->set('Access-Control-Allow-Origin', '*');
        if ($this->checkRequest($event->getRequest())) {
            $headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, OPTIONS, DELETE');
            $headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Auth-Token');
            $headers->set('Access-Control-Allow-Credentials', 'true');
            $headers->set('Access-Control-Max-Age', '3600');
            $headers->set('Cache-Control', 'no-cache, must-revalidate');
        }
    }

    private function checkRequest(Request $request): bool
    {
        $isApi = str_starts_with($request->getRequestUri(), '/api/');
        $isOptions = 'OPTIONS' === $request->getMethod();

        return $isApi && $isOptions;
    }
}
