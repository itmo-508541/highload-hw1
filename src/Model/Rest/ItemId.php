<?php

declare(strict_types=1);

namespace App\Model\Rest;

use Symfony\Component\Serializer\Attribute\Groups;

class ItemId
{
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
