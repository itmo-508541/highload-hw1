<?php

declare(strict_types=1);

namespace App\Tests\Model\Rest;

use App\Model\Rest\Success;
use PHPUnit\Framework\TestCase;

final class SuccessTest extends TestCase
{
    public function testDefaultMessageIsEmptyString(): void
    {
        $success = new Success();

        self::assertSame('', $success->getMessage());
    }

    public function testGetMessageReturnsConstructorValue(): void
    {
        $success = new Success('ok');

        self::assertSame('ok', $success->getMessage());
    }
}
