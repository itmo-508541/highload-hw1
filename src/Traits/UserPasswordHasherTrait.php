<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait UserPasswordHasherTrait
{
    /**
     * @var UserPasswordHasherInterface
     */
    protected $userPassworsHasher;

    /**
     * @required
     */
    public function setUserPasswordHasher(UserPasswordHasherInterface $userPasswordHasher): void
    {
        $this->userPassworsHasher = $userPasswordHasher;
    }
}
