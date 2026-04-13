<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait UserPasswordHasherTrait
{
    protected UserPasswordHasherInterface $userPasswordHasher;

    #[Required]
    public function setUserPasswordHasher(UserPasswordHasherInterface $userPasswordHasher): void
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }
}
