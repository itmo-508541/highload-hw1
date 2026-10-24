<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Mink\Driver\BrowserKitDriver;

/**
 * HTTP-запрос с методом и JSON-телом через клиент драйвера сессии `symfony`:
 * в API Mink таких запросов нет. Ответ читается через сессию Mink.
 *
 * Для контекстов-наследников RawMinkContext.
 */
trait SendsJsonRequests
{
    private function sendJsonRequest(string $method, string $path, ?string $body = null): void
    {
        $session = $this->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $driver = $session->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new \LogicException(\sprintf('JSON requests need the browserless "symfony" session, got driver "%s".', get_debug_type($driver)));
        }

        $server = ['HTTP_ACCEPT' => '*/*'];
        if (null !== $body) {
            $server['CONTENT_TYPE'] = 'application/json';
        }

        $driver->getClient()->request($method, $this->locatePath($path), [], [], $server, $body);
    }

    private function responseJson(bool $associative = true): mixed
    {
        $content = $this->getSession()->getPage()->getContent();

        try {
            return json_decode((string) $content, $associative, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            throw new \RuntimeException(\sprintf("Response body is not JSON: %s\n--- body ---\n%s", $jsonException->getMessage(), $content), 0, $jsonException);
        }
    }
}
