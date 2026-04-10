<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Traits\ValidatorTrait;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidatorTraitTest extends RequiredSetterTestCase
{
    protected function setUp(): void
    {
        $this->object = new class {
            use ValidatorTrait;
        };
        $this->setterName = 'setValidator';
        $this->propertyClass = ValidatorInterface::class;
        $this->propertyName = 'validator';
    }
}
