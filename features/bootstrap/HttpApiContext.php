<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Then;
use Behat\Step\When;

/**
 * Общий контекст, не домен: HTTP-шаги, которые используют оба домена —
 * `item_storage` и `service_health`.
 *
 * Перед каждым сценарием очищает тестовую БД Redis (`REDIS_URL` из `.env.test`):
 * исключение из правила уборки в `@AfterScenario` (ADR 0002) — прогон не зависит
 * от остатков упавшего.
 */
final class HttpApiContext extends RawMinkContext
{
    use SendsJsonRequests;

    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    #[BeforeScenario]
    public function flushTestRedisDatabase(): void
    {
        // БД 0 — данные dev-окружения: чистить можно только отдельную тестовую.
        if (0 === $this->redis->getDbNum()) {
            throw new \LogicException('Refusing to FLUSHDB Redis database 0: REDIS_URL of the test env must select a separate database.');
        }

        $this->redis->flushDB();
    }

    #[When('/^клиент отправляет (GET|POST|PUT) (\S+)$/')]
    public function clientSends(string $method, string $path): void
    {
        $this->sendRequest($method, $path);
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
