<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Домен `item_storage`: записи «ключ — значение» по HTTP — создание, чтение
 * и замена значения через `/items`.
 *
 * Перед каждым сценарием домена очищает тестовую БД Redis (`REDIS_URL`
 * из `.env.test`): в неё пишет только этот домен, это его приватное состояние,
 * и хук фильтруется тегом домена (ADR 0002). Исключение из правила уборки
 * в `@AfterScenario` — прогон не зависит от остатков упавшего.
 */
final class ItemStorageContext extends RawMinkContext
{
    use SendsJsonRequests;

    private ?int $itemId = null;

    /** @var list<array{status: int, body: mixed}> */
    private array $responses = [];

    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    #[BeforeScenario('@domain:item_storage')]
    public function flushTestRedisDatabase(): void
    {
        // БД 0 — данные dev-окружения: чистить можно только отдельную тестовую.
        if (0 === $this->redis->getDbNum()) {
            throw new \LogicException('Refusing to FLUSHDB Redis database 0: REDIS_URL of the test env must select a separate database.');
        }

        $this->redis->flushDB();
    }

    #[Given('/^существует запись со значением (.+)$/')]
    public function thereIsAnItemWithValue(string $value): void
    {
        $this->sendRequest('POST', '/items', \sprintf('{"value": %s}', $value));

        $status = $this->getSession()->getStatusCode();
        $id = $this->createdItemId();
        if (201 !== $status || null === $id) {
            throw new \RuntimeException($this->withResponseBody(\sprintf('Could not create an item with value %s: status %d.', $value, $status)));
        }

        $this->itemId = $id;
    }

    #[When('/^клиент отправляет (POST|PUT) (\S+) с телом:$/')]
    public function clientSendsWithBody(string $method, string $path, PyStringNode $body): void
    {
        $this->sendRequest($method, $path, $body->getRaw());
    }

    #[When('/^клиент отправляет POST (\S+) с Content-Type (\S+) и телом:$/')]
    public function clientPostsWithContentTypeAndBody(string $path, string $contentType, PyStringNode $body): void
    {
        $this->sendRequest('POST', $path, $body->getRaw(), $contentType);
    }

    #[When('/^клиент отправляет POST (\S+) без Content-Type и телом:$/')]
    public function clientPostsWithoutContentTypeAndBody(string $path, PyStringNode $body): void
    {
        $this->sendRequest('POST', $path, $body->getRaw(), null);
    }

    #[When('/^клиент отправляет GET (\S+) с Accept (\S+)$/')]
    public function clientSendsGetWithAccept(string $path, string $accept): void
    {
        $this->sendRequest('GET', $path, accept: $accept);
    }

    #[When('клиент читает эту запись')]
    public function clientReadsTheItem(): void
    {
        $this->sendRequest('GET', $this->itemPath());
    }

    /**
     * Другой экземпляр — новое ядро с новым контейнером и новым соединением
     * с тем же Redis. Ядро гасится явно, а не ставкой на перезагрузку
     * `KernelBrowser` перед повторным запросом.
     *
     * Новое ядро живёт в том же процессе PHP: хранилище в памяти процесса прошло бы
     * чтение. Поэтому запись сначала ищется вне процесса — в тестовой БД Redis.
     */
    #[When('клиент читает эту запись через другой экземпляр сервиса')]
    public function clientReadsTheItemThroughAnotherInstance(): void
    {
        $client = $this->browserKitClient();
        if (!$client instanceof KernelBrowser) {
            throw new \LogicException(\sprintf('Another service instance needs a KernelBrowser, got "%s".', get_debug_type($client)));
        }

        if (0 === $this->redis->dbSize()) {
            throw new \RuntimeException('Expected the created item outside the Behat process, but the test Redis database is empty.');
        }

        $client->getKernel()->shutdown();
        $this->sendRequest('GET', $this->itemPath());
    }

    #[When('клиент заменяет эту запись телом:')]
    public function clientReplacesTheItemWith(PyStringNode $body): void
    {
        $this->sendRequest('PUT', $this->itemPath(), $body->getRaw());
    }

    #[When('клиент дважды отправляет POST /items с телом:')]
    public function clientSendsPostTwice(PyStringNode $body): void
    {
        for ($i = 0; $i < 2; ++$i) {
            $this->sendRequest('POST', '/items', $body->getRaw());
            $this->responses[] = [
                'status' => $this->getSession()->getStatusCode(),
                'body' => $this->responseJson(),
            ];
        }
    }

    #[Then('оба ответа имеют код :code')]
    public function bothResponsesHaveStatus(int $code): void
    {
        $statuses = array_column($this->responses, 'status');
        if ([$code, $code] !== $statuses) {
            throw new \RuntimeException(\sprintf('Expected both statuses %d, got [%s].', $code, implode(', ', $statuses)));
        }
    }

    #[Then('id в ответах различаются')]
    public function idsInResponsesDiffer(): void
    {
        $ids = array_map(static fn (array $response): ?int => self::itemIdIn($response['body']), $this->responses);
        if (2 !== \count($ids) || null === $ids[0] || null === $ids[1] || $ids[0] === $ids[1]) {
            throw new \RuntimeException(\sprintf('Expected two different integer ids, got %s.', json_encode($ids)));
        }
    }

    #[Then('тело ответа — объект с единственным ключом id, положительным целым')]
    public function responseBodyIsPositiveId(): void
    {
        if (null === $this->createdItemId()) {
            throw new \RuntimeException($this->withResponseBody('Expected {"id": <positive int>}.'));
        }
    }

    #[Then('тело ответа — id этой записи')]
    public function responseBodyIsTheItemId(): void
    {
        if (['id' => $this->itemId] !== $this->responseJson()) {
            throw new \RuntimeException($this->withResponseBody(\sprintf('Expected {"id": %d}.', $this->itemId)));
        }
    }

    /**
     * Сравнение строгое: JSON-тип значения тоже должен совпасть (`1` ≠ `"1"`).
     */
    #[Then('тело ответа — JSON:')]
    public function responseBodyShouldBeJson(PyStringNode $expected): void
    {
        $expectedValue = json_decode($expected->getRaw(), true, 512, \JSON_THROW_ON_ERROR);
        if ($this->responseJson() !== $expectedValue) {
            throw new \RuntimeException($this->withResponseBody(\sprintf('Expected JSON body %s.', $expected->getRaw())));
        }
    }

    /**
     * Тестовая БД Redis очищается перед сценарием, поэтому «не изменилось» — «пуста»:
     * проверка не повторяет схему ключей `ItemRepository`.
     */
    #[Then('хранилище записей не изменилось')]
    public function storageIsUntouched(): void
    {
        $size = $this->redis->dbSize();
        if (0 !== $size) {
            throw new \RuntimeException(\sprintf('Expected the test Redis database to stay empty, found %s keys: %s.', var_export($size, true), implode(', ', $this->redis->keys('*') ?: [])));
        }
    }

    private function createdItemId(): ?int
    {
        return self::itemIdIn($this->responseJson());
    }

    /**
     * id из тела ответа создания: тело — ровно `{"id": N}` с положительным целым `N`.
     */
    private static function itemIdIn(mixed $body): ?int
    {
        if (!\is_array($body) || ['id'] !== array_keys($body) || !\is_int($body['id']) || $body['id'] <= 0) {
            return null;
        }

        return $body['id'];
    }

    private function itemPath(): string
    {
        if (null === $this->itemId) {
            throw new \LogicException('No item in this scenario: add the step "существует запись со значением …" first.');
        }

        return '/items/'.$this->itemId;
    }
}
