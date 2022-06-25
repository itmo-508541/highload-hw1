<?php

declare(strict_types=1);

namespace App\Model\Rest;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 */
class AuthToken
{
    /**
     * @JMS\Expose()
     * @JMS\Type(name="string")
     */
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }
}
