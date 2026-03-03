<?php

declare(strict_types=1);

namespace App\Model\Rest;

use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
class AuthToken
{
    public function __construct(
        #[JMS\Expose]
        #[JMS\Type(name: 'string')]
        private readonly string $token,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
