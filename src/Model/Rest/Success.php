<?php

declare(strict_types=1);

namespace App\Model\Rest;

use JMS\Serializer\Annotation as JMS;

class Success
{
    /**
     * @JMS\Type(name="string")
     */
    private $message;

    public function __construct(string $message = '')
    {
        $this->message = $message;
    }
}
