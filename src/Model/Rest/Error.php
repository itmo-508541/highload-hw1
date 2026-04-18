<?php

declare(strict_types=1);

namespace App\Model\Rest;

use Symfony\Component\Serializer\Attribute\Groups;

abstract class Error
{
    #[Groups(['api'])]
    public string $code;

    #[Groups(['api'])]
    public string $message;
}
