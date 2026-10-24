<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Домен `item_storage`: записи «ключ — значение» по HTTP — создание, чтение
 * и замена значения через `/items`.
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

    #[Given('/^существует запись со значением (.+)$/')]
    public function thereIsAnItemWithValue(string $value): void
    {
        $this->sendJsonRequest('POST', '/items', \sprintf('{"value": %s}', $value));

        $status = $this->getSession()->getStatusCode();
        $body = $this->responseJson();
        if (201 !== $status || !\is_array($body) || !\is_int($body['id'] ?? null)) {
            throw new \RuntimeException(\sprintf("Could not create an item with value %s: status %d.\n--- body ---\n%s", $value, $status, $this->getSession()->getPage()->getContent()));
        }

        $this->itemId = $body['id'];
    }

    #[When('клиент читает эту запись')]
    public function clientReadsTheItem(): void
    {
        $this->sendJsonRequest('GET', $this->itemPath());
    }

    /**
     * Другой экземпляр — новое ядро с новым контейнером и новым соединением
     * с тем же Redis. Ядро гасится явно, а не ставкой на перезагрузку
     * `KernelBrowser` перед повторным запросом.
     */
    #[When('клиент читает эту запись через другой экземпляр сервиса')]
    public function clientReadsTheItemThroughAnotherInstance(): void
    {
        $driver = $this->getSession()->getDriver();
        $client = $driver instanceof BrowserKitDriver ? $driver->getClient() : null;
        if (!$client instanceof KernelBrowser) {
            throw new \LogicException(\sprintf('Another service instance needs a KernelBrowser, got "%s".', get_debug_type($client)));
        }

        $client->getKernel()->shutdown();
        $this->sendJsonRequest('GET', $this->itemPath());
    }

    #[When('клиент заменяет эту запись телом:')]
    public function clientReplacesTheItemWith(PyStringNode $body): void
    {
        $this->sendJsonRequest('PUT', $this->itemPath(), $body->getRaw());
    }

    #[When('клиент дважды отправляет POST /items с телом:')]
    public function clientSendsPostTwice(PyStringNode $body): void
    {
        for ($i = 0; $i < 2; ++$i) {
            $this->sendJsonRequest('POST', '/items', $body->getRaw());
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
        $ids = array_map(static fn (array $response): mixed => \is_array($response['body']) ? ($response['body']['id'] ?? null) : null, $this->responses);
        if (2 !== \count($ids) || !\is_int($ids[0]) || !\is_int($ids[1]) || $ids[0] === $ids[1]) {
            throw new \RuntimeException(\sprintf('Expected two different integer ids, got %s.', json_encode($ids)));
        }
    }

    #[Then('тело ответа — объект с единственным ключом id, положительным целым')]
    public function responseBodyIsPositiveId(): void
    {
        $body = $this->responseJson();
        if (!\is_array($body) || ['id'] !== array_keys($body) || !\is_int($body['id']) || $body['id'] <= 0) {
            throw new \RuntimeException(\sprintf('Expected {"id": <positive int>}, got %s.', $this->getSession()->getPage()->getContent()));
        }
    }

    #[Then('тело ответа — id этой записи')]
    public function responseBodyIsTheItemId(): void
    {
        if (['id' => $this->itemId] !== $this->responseJson()) {
            throw new \RuntimeException(\sprintf('Expected {"id": %d}, got %s.', $this->itemId, $this->getSession()->getPage()->getContent()));
        }
    }

    #[Then('хранилище записей не изменилось')]
    public function storageIsUntouched(): void
    {
        $keys = array_merge($this->redis->keys('item:*') ?: [], $this->redis->exists('items:seq') ? ['items:seq'] : []);
        if ([] !== $keys) {
            throw new \RuntimeException(\sprintf('Expected no item keys in Redis, found: %s.', implode(', ', $keys)));
        }
    }

    private function itemPath(): string
    {
        if (null === $this->itemId) {
            throw new \LogicException('No item in this scenario: add the step "существует запись со значением …" first.');
        }

        return '/items/'.$this->itemId;
    }
}
