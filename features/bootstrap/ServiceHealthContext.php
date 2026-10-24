<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Given;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Домен `service_health`: готовность сервиса принимать запросы (`GET /health`).
 */
final class ServiceHealthContext extends RawMinkContext
{
    public function __construct(
        private readonly ContainerInterface $driverContainer,
    ) {
    }

    /**
     * Подменяет `Redis` в контейнере ядра драйвера, пока сервис не создан.
     * Действует только на первый запрос сценария: перед вторым ядро
     * перезагрузится, — поэтому после этого шага запрос ровно один.
     */
    #[Given('хранилище записей не отвечает')]
    public function storageDoesNotRespond(): void
    {
        $this->driverContainer->set(\Redis::class, new class extends \Redis {
            #[\Override]
            public function ping(?string $message = null): \Redis|string|bool
            {
                throw new \RedisException('Connection refused (test double).');
            }
        });
    }
}
