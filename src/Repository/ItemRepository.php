<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Rest\ItemValue;

/**
 * Записи `/items` в Redis (ADR 0004): счётчик id `items:seq`,
 * значение — JSON-строка `{"value": X}` под ключом `item:{id}`, чтобы сохранялся JSON-тип.
 */
class ItemRepository
{
    private const string SEQUENCE_KEY = 'items:seq';

    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    public function create(ItemValue $item): int
    {
        $id = $this->redis->incr(self::SEQUENCE_KEY);
        if (!\is_int($id)) {
            throw new \RuntimeException(\sprintf('Redis INCR %s failed.', self::SEQUENCE_KEY));
        }

        if (true !== $this->redis->set($this->key($id), $this->encode($item))) {
            throw new \RuntimeException(\sprintf('Redis SET %s failed.', $this->key($id)));
        }

        return $id;
    }

    public function find(int $id): ?ItemValue
    {
        $raw = $this->redis->get($this->key($id));
        if (!\is_string($raw)) {
            return null;
        }

        /** @var array{value: string|int|float|bool} $data */
        $data = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);

        return new ItemValue($data['value']);
    }

    /**
     * `SET … XX` пишет только в существующий ключ: замена не создаёт запись.
     *
     * @return bool false, если записи с таким id нет
     */
    public function replace(int $id, ItemValue $item): bool
    {
        return true === $this->redis->set($this->key($id), $this->encode($item), ['XX']);
    }

    private function key(int $id): string
    {
        return 'item:'.$id;
    }

    private function encode(ItemValue $item): string
    {
        return json_encode(['value' => $item->getValue()], \JSON_THROW_ON_ERROR);
    }
}
