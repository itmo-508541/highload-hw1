<?php

declare(strict_types=1);

namespace App\Repository;

interface ConfigRepositoryInterface
{
    public function getValue(array $params, string $suffix, mixed $default = null): ?string;

    public function setValue(array $params, string $suffix, mixed $value): void;
}
