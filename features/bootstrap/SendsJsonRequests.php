<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Mink\Driver\BrowserKitDriver;
use Symfony\Component\BrowserKit\AbstractBrowser;

/**
 * HTTP-запрос с методом и телом через клиент драйвера сессии `symfony`:
 * в API Mink таких запросов нет. Ответ читается через сессию Mink.
 *
 * Для контекстов-наследников RawMinkContext.
 */
trait SendsJsonRequests
{
    /**
     * `$contentType = null` — тело без заголовка `Content-Type`: передаётся пустой
     * `CONTENT_TYPE`, как его отдают nginx и PHP-FPM. Иначе `Request::create`
     * подставил бы `application/x-www-form-urlencoded`.
     */
    private function sendRequest(string $method, string $path, ?string $body = null, ?string $contentType = 'application/json', string $accept = '*/*'): void
    {
        $server = ['HTTP_ACCEPT' => $accept];
        if (null !== $body) {
            $server['CONTENT_TYPE'] = $contentType ?? '';
        }

        // PHP-FPM раскладывает тело формы в `$_POST` сам, а BrowserKit кладёт
        // в `$request->request` только параметры запроса — передаём их явно.
        $parameters = [];
        if (null !== $body && 'application/x-www-form-urlencoded' === $contentType) {
            parse_str($body, $parameters);
        }

        $this->browserKitClient()->request($method, $this->locatePath($path), $parameters, [], $server, $body);
    }

    /**
     * @return AbstractBrowser<object, object>
     */
    private function browserKitClient(): AbstractBrowser
    {
        $session = $this->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $driver = $session->getDriver();
        if (!$driver instanceof BrowserKitDriver) {
            throw new \LogicException(\sprintf('JSON requests need the browserless "symfony" session, got driver "%s".', get_debug_type($driver)));
        }

        return $driver->getClient();
    }

    private function responseJson(bool $associative = true): mixed
    {
        try {
            return json_decode($this->responseContent(), $associative, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            throw new \RuntimeException($this->withResponseBody('Response body is not JSON: '.$jsonException->getMessage()), 0, $jsonException);
        }
    }

    private function responseContent(): string
    {
        return (string) $this->getSession()->getPage()->getContent();
    }

    /**
     * Сообщение о падении шага с телом ответа: по нему видно, что ответил сервис.
     */
    private function withResponseBody(string $message): string
    {
        return $message."\n--- body ---\n".$this->responseContent();
    }
}
