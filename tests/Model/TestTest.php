<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\Test;
use PHPUnit\Framework\TestCase;

final class TestTest extends TestCase
{
    public function testCreate(): void
    {
        $model = new Test(1, 2);

        self::assertSame(1, $model->getPublicData());
        self::assertSame(2, $model->getSecretData());
    }
}
