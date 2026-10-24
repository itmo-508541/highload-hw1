<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Then;
use Behat\Step\When;

/**
 * Общий контекст, не домен: HTTP-шаги, которые используют несколько доменов.
 */
final class HttpApiContext extends RawMinkContext
{
    use SendsJsonRequests;

    #[When('/^клиент отправляет GET (\S+)$/')]
    public function clientSendsGet(string $path): void
    {
        $this->sendRequest('GET', $path);
    }

    #[Then('код ответа :code')]
    public function responseStatusShouldBe(int $code): void
    {
        $actual = $this->getSession()->getStatusCode();
        if ($actual !== $code) {
            throw new \RuntimeException($this->withResponseBody(\sprintf('Expected status %d, got %d.', $code, $actual)));
        }
    }

    #[Then('/^ответ имеет Content-Type (\S+)$/')]
    public function responseContentTypeShouldBe(string $mediaType): void
    {
        $header = (string) $this->getSession()->getResponseHeader('Content-Type');
        $actual = strtolower(trim(explode(';', $header)[0]));
        if ($actual !== $mediaType) {
            throw new \RuntimeException(\sprintf('Expected Content-Type "%s", got "%s".', $mediaType, $header));
        }
    }

    #[Then('тело ответа — JSON-объект')]
    public function responseBodyShouldBeJsonObject(): void
    {
        $body = $this->responseJson(false);
        if (!$body instanceof \stdClass) {
            throw new \RuntimeException($this->withResponseBody(\sprintf('Expected a JSON object, got %s.', get_debug_type($body))));
        }
    }
}
