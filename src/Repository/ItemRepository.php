<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Записи `/items` в Redis (ADR 0004): счётчик id `items:seq`,
 * значение — JSON-строка `{"value": X}` под ключом `item:{id}`, чтобы сохранялся JSON-тип.
 *
 * Интерфейс — скаляры: модели ответа собирают резолвер и контроллеры.
 */
class ItemRepository
{
    private const string SEQUENCE_KEY = 'items:seq';

    public function __construct(
        private readonly \Redis $redis,
    ) {
    }

    public function create(string|int|float|bool $value): int
    {
        $id = $this->redis->incr(self::SEQUENCE_KEY);
        if (!\is_int($id)) {
            throw new \RuntimeException(\sprintf('Redis INCR %s failed.', self::SEQUENCE_KEY));
        }

        if (true !== $this->redis->set($this->key($id), $this->encode($value))) {
            throw new \RuntimeException(\sprintf('Redis SET %s failed.', $this->key($id)));
        }

        return $id;
    }

    /**
     * @return string|int|float|bool|null null, если записи с таким id нет
     */
    public function find(int $id): string|int|float|bool|null
    {
        $raw = $this->redis->get($this->key($id));
        if (!\is_string($raw)) {
            return null;
        }

        /** @var array{value: string|int|float|bool} $data */
        $data = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);

        return $data['value'];
    }

    /**
     * `SET … XX` пишет только в существующий ключ: замена не создаёт запись.
     *
     * @return bool false, если записи с таким id нет
     */
    public function replace(int $id, string|int|float|bool $value): bool
    {
        return true === $this->redis->set($this->key($id), $this->encode($value), ['XX']);
    }

    private function key(int $id): string
    {
        return 'item:'.$id;
    }

    /**
     * Без `JSON_PRESERVE_ZERO_FRACTION` `1.0` записалось бы как `1` и читалось целым.
     */
    private function encode(string|int|float|bool $value): string
    {
        return json_encode(['value' => $value], \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION);
    }
}
