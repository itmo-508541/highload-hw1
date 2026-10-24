<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Then;
use Behat\Step\When;

/**
 * Общий контекст, не домен: HTTP-запросы с JSON-телом и проверки ответа,
 * нужные доменам `item_storage` и `service_health`.
 *
 * Перед каждым сценарием очищает тестовую БД Redis (`REDIS_URL` из `.env.test`).
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
        $this->sendJsonRequest($method, $path);
    }

    #[When('/^клиент отправляет (GET|POST|PUT) (\S+) с телом:$/')]
    public function clientSendsWithBody(string $method, string $path, PyStringNode $body): void
    {
        $this->sendJsonRequest($method, $path, $body->getRaw());
    }

    #[Then('код ответа :code')]
    public function responseStatusShouldBe(int $code): void
    {
        $actual = $this->getSession()->getStatusCode();
        if ($actual !== $code) {
            throw new \RuntimeException(\sprintf("Expected status %d, got %d.\n--- body ---\n%s", $code, $actual, $this->getSession()->getPage()->getContent()));
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
            throw new \RuntimeException(\sprintf('Expected a JSON object, got %s.', get_debug_type($body)));
        }
    }

    /**
     * Сравнение строгое: JSON-тип значения тоже должен совпасть (`1` ≠ `"1"`).
     */
    #[Then('тело ответа — JSON:')]
    public function responseBodyShouldBeJson(PyStringNode $expected): void
    {
        $expectedValue = json_decode($expected->getRaw(), true, 512, \JSON_THROW_ON_ERROR);
        $actualValue = $this->responseJson();
        if ($actualValue !== $expectedValue) {
            throw new \RuntimeException(\sprintf('Expected JSON body %s, got %s.', $expected->getRaw(), $this->getSession()->getPage()->getContent()));
        }
    }
}
