<?php

declare(strict_types=1);

namespace App\Tests\Traits;

use App\Service\LoaderConfig;
use App\Traits\LoaderConfigTrait;

final class LoaderConfigTraitTest extends RequiredSetterTestCase
{
    protected function setUp(): void
    {
        $this->object = new class {
            use LoaderConfigTrait;
        };
        $this->setterName = 'setLoaderConfig';
        $this->propertyClass = LoaderConfig::class;
        $this->propertyName = 'loaderConfig';
    }
}
