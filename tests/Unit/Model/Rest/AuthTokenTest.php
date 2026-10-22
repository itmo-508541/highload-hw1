<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Rest;

use App\Model\Rest\AuthToken;
use PHPUnit\Framework\TestCase;

final class AuthTokenTest extends TestCase
{
    public function testGetTokenReturnsConstructorValue(): void
    {
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_Xjsl0df3L_W-299CBdTg';
        $authToken = new AuthToken($token);

        self::assertSame($token, $authToken->getToken());
    }
}
