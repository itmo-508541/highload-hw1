<?php

declare(strict_types=1);

namespace App\Model;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 */
class Test
{
    public function __construct(
        /**
         * @JMS\Expose()
         * @JMS\Type(name="string")
         */
        private int $publicData,
        /**
         * @JMS\Exclude()
         */
        private int $secretData
    )
    {
    }

    public function getSecretData(): int
    {
        return $this->secretData;
    }

    public function getPublicData(): int
    {
        return $this->publicData;
    }
}
