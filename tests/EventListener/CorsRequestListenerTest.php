<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\CorsRequestListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CorsRequestListenerTest extends TestCase
{
    public function testGetSubscribedEventsReturnsExpectedConfiguration(): void
    {
        $expected = [
            RequestEvent::class => ['onRequestEvent', 5000],
            ResponseEvent::class => 'onResponseEvent',
        ];

        self::assertSame($expected, CorsRequestListener::getSubscribedEvents());
    }

    public function testOnRequestEventSetsResponseForApiOptionsRequest(): void
    {
        $listener = new CorsRequestListener();
        $event = $this->createRequestEvent(Request::create('/api/foo', 'OPTIONS'));

        $listener->onRequestEvent($event);

        self::assertTrue($event->hasResponse());
        self::assertInstanceOf(Response::class, $event->getResponse());
    }

    public function testOnRequestEventDoesNotSetResponseForNonApiOptions(): void
    {
        $listener = new CorsRequestListener();
        $event = $this->createRequestEvent(Request::create('/other', 'OPTIONS'));

        $listener->onRequestEvent($event);

        self::assertFalse($event->hasResponse());
    }

    public function testOnRequestEventDoesNotSetResponseForApiNonOptions(): void
    {
        $listener = new CorsRequestListener();
        $event = $this->createRequestEvent(Request::create('/api/foo', 'GET'));

        $listener->onRequestEvent($event);

        self::assertFalse($event->hasResponse());
    }

    public function testOnRequestEventDoesNotSetResponseForApiPathWithoutTrailingSegment(): void
    {
        $listener = new CorsRequestListener();
        $event = $this->createRequestEvent(Request::create('/api', 'OPTIONS'));

        $listener->onRequestEvent($event);

        self::assertFalse($event->hasResponse());
    }

    public function testOnResponseEventAlwaysSetsAllowOrigin(): void
    {
        $listener = new CorsRequestListener();
        $response = new Response();
        $event = $this->createResponseEvent(Request::create('/', 'GET'), $response);

        $listener->onResponseEvent($event);

        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testOnResponseEventSetsFullCorsHeadersForApiOptions(): void
    {
        $listener = new CorsRequestListener();
        $response = new Response();
        $event = $this->createResponseEvent(Request::create('/api/resource', 'OPTIONS'), $response);

        $listener->onResponseEvent($event);

        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertSame('POST, GET, PUT, OPTIONS, DELETE', $response->headers->get('Access-Control-Allow-Methods'));
        self::assertSame(
            'Authorization, Content-Type, Accept, X-Auth-Token',
            $response->headers->get('Access-Control-Allow-Headers')
        );
        self::assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
        self::assertSame('3600', $response->headers->get('Access-Control-Max-Age'));
        $cacheControl = $response->headers->get('Cache-Control');
        self::assertNotNull($cacheControl);
        self::assertStringContainsString('no-cache', $cacheControl);
        self::assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function testOnResponseEventSetsOnlyAllowOriginForNonPreflightApiRequest(): void
    {
        $listener = new CorsRequestListener();
        $response = new Response();
        $event = $this->createResponseEvent(Request::create('/api/users', 'GET'), $response);

        $listener->onResponseEvent($event);

        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Methods'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Headers'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
        self::assertFalse($response->headers->has('Access-Control-Max-Age'));
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
