<?php

declare(strict_types=1);

namespace App\Model\Rest;

use JMS\Serializer\Annotation as JMS;

abstract class Error
{
    #[JMS\Type(name: 'string')]
    public string $code;

    #[JMS\Type(name: 'string')]
    public string $message;
}
