<?php

declare(strict_types=1);

namespace App\Traits;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

trait JWTManagerTrait
{
    /**
     * @var JWTTokenManagerInterface
     */
    protected $jwtManager;

    /**
     * @required
     */
    public function setJWTManager(JWTTokenManagerInterface $jwtManager): void
    {
        $this->jwtManager = $jwtManager;
    }
}
