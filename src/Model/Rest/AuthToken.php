<?php

declare(strict_types=1);

namespace App\Model\Rest;

use Symfony\Component\Serializer\Attribute\Groups;

class AuthToken
{
    public function __construct(
        #[Groups(['api'])]
        private readonly string $token,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
