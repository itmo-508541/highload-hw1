<?php

declare(strict_types=1);

namespace App\Controller\Item;

final class ItemRoute
{
    /**
     * Требование к `{id}` в роутах `/items/{id}`: положительное целое без ведущих
     * нулей и не длиннее 18 цифр. Более длинная строка не приводится к `int` аргумента
     * контроллера (`TypeError` → 500), а под требование не подходит и даёт 404 роутера.
     */
    public const string ID_REQUIREMENT = '[1-9]\d{0,17}';
}
