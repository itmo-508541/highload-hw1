<?php

declare(strict_types=1);

namespace App\Tests\Integration\Session;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

/**
 * Контур E2E пишет cookie-сессии в Redis (как dev/prod), а не в mock_file из when@test.
 */
final class E2eSessionStorageTest extends KernelTestCase
{
    public function testE2eSessionIsStoredInRedis(): void
    {
        self::bootKernel(['environment' => 'e2e', 'debug' => true]);

        /** @var SessionFactoryInterface $factory */
        $factory = self::getContainer()->get('session.factory');
        $session = $factory->createSession();
        $session->start();
        $session->set('_e2e_storage_probe', '1');
        $session->save();

        $sessionId = $session->getId();
        self::assertNotSame('', $sessionId);

        /** @var \Redis $redis */
        $redis = self::getContainer()->get(\Redis::class);
        $key = 'sf_s:'.$sessionId;

        self::assertNotFalse(
            $redis->exists($key),
            \sprintf('Expected Redis key "%s" after saving an e2e session (Redis handler as in dev/prod).', $key),
        );
    }

    #[\Override]
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}
