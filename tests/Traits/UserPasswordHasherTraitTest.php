<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Traits\UserPasswordHasherTrait;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserPasswordHasherTraitTest extends RequiredSetterTestCase
{
    protected function setUp(): void
    {
        $this->object = new class {
            use UserPasswordHasherTrait;
        };
        $this->setterName = 'setUserPasswordHasher';
        $this->propertyClass = UserPasswordHasherInterface::class;
        $this->propertyName = 'userPasswordHasher';
    }
}
