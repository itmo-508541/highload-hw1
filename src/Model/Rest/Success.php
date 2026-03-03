<?php

declare(strict_types=1);

namespace App\Model\Rest;

use JMS\Serializer\Annotation as JMS;

class Success
{
    public function __construct(
        #[JMS\Type(name: 'string')]
        private readonly string $message = '',
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
