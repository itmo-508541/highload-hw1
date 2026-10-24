<?php

declare(strict_types=1);

namespace App\Behat;

use Behat\Hook\AfterScenario;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Given;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Домен `service_health`: готовность сервиса принимать запросы (`GET /health`).
 */
final class ServiceHealthContext extends RawMinkContext
{
    /** Адрес, на котором Redis заведомо не слушает. */
    private const string UNREACHABLE_REDIS_URL = 'redis://127.0.0.1:1';

    /** @var array{env: mixed, server: mixed}|null */
    private ?array $replacedRedisUrl = null;

    public function __construct(
        private readonly ContainerInterface $driverContainer,
    ) {
    }

    /**
     * Подменяет `Redis` в контейнере ядра драйвера, пока сервис не создан.
     * Действует только на первый запрос сценария: перед вторым ядро
     * перезагрузится, — поэтому после этого шага запрос ровно один.
     */
    #[Given('хранилище записей перестало отвечать')]
    public function storageStoppedResponding(): void
    {
        $this->driverContainer->set(\Redis::class, new class extends \Redis {
            #[\Override]
            public function ping(?string $message = null): \Redis|string|bool
            {
                throw new \RedisException('Connection refused (test double).');
            }
        });
    }

    /**
     * `%env(REDIS_URL)%` разрешается при создании сервиса `Redis`, то есть уже
     * в запросе: недоступный адрес роняет `RedisAdapter::createConnection`.
     *
     * Подмена меняет `$_ENV` и `$_SERVER` — глобальное состояние процесса Behat,
     * а не приватное состояние контекста. Прежнее значение возвращает
     * `restoreRedisUrl()` этого же контекста.
     */
    #[Given('хранилище записей не принимает подключений')]
    public function storageRefusesConnections(): void
    {
        $this->replacedRedisUrl = ['env' => $_ENV['REDIS_URL'] ?? null, 'server' => $_SERVER['REDIS_URL'] ?? null];
        $_ENV['REDIS_URL'] = $_SERVER['REDIS_URL'] = self::UNREACHABLE_REDIS_URL;
    }

    #[AfterScenario]
    public function restoreRedisUrl(): void
    {
        if (null === $this->replacedRedisUrl) {
            return;
        }

        $this->restore($_ENV, $this->replacedRedisUrl['env']);
        $this->restore($_SERVER, $this->replacedRedisUrl['server']);
        $this->replacedRedisUrl = null;
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function restore(array &$variables, mixed $value): void
    {
        if (null === $value) {
            unset($variables['REDIS_URL']);
        } else {
            $variables['REDIS_URL'] = $value;
        }
    }
}
