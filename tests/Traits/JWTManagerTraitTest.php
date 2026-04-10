<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Traits\JWTManagerTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class JWTManagerTraitTest extends RequiredSetterTestCase
{
    protected function setUp(): void
    {
        $this->object = new class {
            use JWTManagerTrait;
        };
        $this->setterName = 'setJWTManager';
        $this->propertyClass = JWTTokenManagerInterface::class;
        $this->propertyName = 'jwtManager';
    }
}
