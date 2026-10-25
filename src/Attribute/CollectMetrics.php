<?php

declare(strict_types=1);

namespace App\Attribute;

#[\Attribute]
class CollectMetrics
{
    public function __construct(protected string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
