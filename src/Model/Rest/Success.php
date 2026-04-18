<?php

declare(strict_types=1);

namespace App\Model\Rest;

use Symfony\Component\Serializer\Attribute\Groups;

class Success
{
    public function __construct(
        #[Groups(['api'])]
        private readonly string $message = '',
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
