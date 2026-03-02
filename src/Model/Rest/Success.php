<?php

declare(strict_types=1);

namespace App\Model\Rest;

use JMS\Serializer\Annotation as JMS;

class Success
{
    /**
     * @JMS\Type(name="string")
     */
    private string $message;

    public function __construct(string $message = '')
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
