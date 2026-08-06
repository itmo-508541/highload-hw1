<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

final class HomepageTest extends PantherTestCase
{
    public function testVisit(): void
    {
        $client = self::createPantherClient([
            'env' => ['APP_ENV' => 'e2e'],
        ]);
        $client->request('GET', '/');

        self::assertStringEndsWith('/', $client->getCurrentURL());
        self::assertSame(200, $client->getInternalResponse()->getStatusCode());
    }
}
