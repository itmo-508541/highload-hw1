<?php

declare(strict_types=1);

namespace App\Model\Rest;

use Symfony\Component\Serializer\Attribute\Groups;

class ItemId
{
    /**
     * Требование к `{id}` в роутах `/items/{id}`: положительное целое без ведущих
     * нулей и не длиннее 18 цифр. Более длинная строка не приводится к `int` аргумента
     * контроллера (`TypeError` → 500), а под требование не подходит и даёт 404 роутера.
     */
    public const string ROUTE_REQUIREMENT = '[1-9]\d{0,17}';

    public function __construct(
        #[Groups(['api'])]
        private readonly int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
