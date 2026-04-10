<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Traits\EntityManagerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class EntityManagerTraitTest extends RequiredSetterTestCase
{
    protected function setUp(): void
    {
        $this->object = new class {
            use EntityManagerTrait;
        };
        $this->setterName = 'setEntityManager';
        $this->propertyClass = EntityManagerInterface::class;
        $this->propertyName = 'em';
    }
}
