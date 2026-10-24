<?php

declare(strict_types=1);

namespace App\Model\Rest;

use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Значение записи: ответ `GET /items/{id}` и разобранное тело `POST`/`PUT`
 * (`App\ValueResolver\ItemValueResolver`).
 */
class ItemValue
{
    public function __construct(
        #[Groups(['api'])]
        private readonly string|int|float|bool $value,
    ) {
    }

    public function getValue(): string|int|float|bool
    {
        return $this->value;
    }
}
