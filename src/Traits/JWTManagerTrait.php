<?php

declare(strict_types=1);

namespace App\Traits;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait JWTManagerTrait
{
    /**
     * @var JWTTokenManagerInterface
     */
    protected $jwtManager;

    #[Required]
    public function setJWTManager(JWTTokenManagerInterface $jwtManager): void
    {
        $this->jwtManager = $jwtManager;
    }
}
