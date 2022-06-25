<?php

declare(strict_types=1);

namespace App\Model;

use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 */
class Test
{
    /**
     * @JMS\Exclude()
     */
    private $secretData;

    /**
     * @JMS\Expose()
     * @JMS\Type(name="string")
     */
    private $publicData;

    public function __construct(int $publicData, int $secretData)
    {
        $this->publicData = $publicData;
        $this->secretData = $secretData;
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
