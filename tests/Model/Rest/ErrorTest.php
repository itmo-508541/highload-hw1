<?php

declare(strict_types=1);

namespace App\Tests\Model\Rest;

use App\Model\Rest\Error;
use PHPUnit\Framework\TestCase;

final class ErrorTest extends TestCase
{
    public function testPublicPropertiesCanBeSetAndRead(): void
    {
        /** @var Error $error */
        $error = new class extends Error {
        };

        $error->code = 'E_VALIDATION';
        $error->message = 'Invalid input';

        self::assertSame('E_VALIDATION', $error->code);
        self::assertSame('Invalid input', $error->message);
    }
}
