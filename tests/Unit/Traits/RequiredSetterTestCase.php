<?php

declare(strict_types=1);

namespace App\Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\Attribute\Required;

class RequiredSetterTestCase extends TestCase
{
    protected object $object;

    protected string $setterName;

    protected string $propertyClass;

    protected string $propertyName;

    public function testRequiredSetter(): void
    {
        $reflection = new \ReflectionClass($this->object);
        $methods = $reflection->getMethods();

        $setterFound = false;
        foreach ($methods as $method) {
            if ($method->getName() === $this->setterName) {
                $setterFound = true;

                // Проверяем, есть ли атрибут Required
                $attributes = $method->getAttributes(Required::class);
                self::assertNotEmpty($attributes, \sprintf('Method %s should have Required attribute', $this->setterName));

                // Проверяем параметры метода
                $parameters = $method->getParameters();
                self::assertCount(1, $parameters, \sprintf('Method %s should have exactly one parameter', $this->setterName));

                $param = $parameters[0];
                /** @var \ReflectionNamedType $type */
                $type = $param->getType();

                // Проверяем тип параметра
                self::assertNotNull($param->getType(), 'Parameter should have a type');
                self::assertFalse($type->isBuiltin(), 'Parameter should be an object');
                self::assertSame($this->propertyClass, $type->getName(), \sprintf('Parameter should be %s', $this->propertyClass));

                break;
            }
        }

        self::assertTrue($setterFound, \sprintf('Method %s should exist', $this->setterName));
    }

    public function testSetterPopulatesTraitProperty(): void
    {
        $reflection = new \ReflectionClass($this->object);

        // Получаем свойства объекта
        $properties = $reflection->getProperties();

        // Ищем свойство, соответствующее трейту
        $traitProperty = null;
        foreach ($properties as $property) {
            if ($property->getName() === $this->propertyName) {
                $traitProperty = $property;
                break;
            }
        }

        self::assertNotNull($traitProperty, \sprintf('Trait property %s should exist', $this->propertyName));

        // Создаем мок для зависимости
        $dependency = self::createStub($this->propertyClass);

        // Вызываем сеттер
        $setter = $reflection->getMethod($this->setterName);
        $setter->invoke($this->object, $dependency);

        // Проверяем, что свойство было заполнено
        $value = $traitProperty->getValue($this->object);

        self::assertSame($dependency, $value, 'Property should be set to the provided dependency');
    }
}
