<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Rest\Success;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    /**
     * Замыкание, а не `\Redis`: сервис подключается при создании, и падение
     * подключения должно случиться внутри `try`, а не при сборке контроллера.
     *
     * @param \Closure(): \Redis $redis
     */
    public function __construct(
        #[AutowireServiceClosure(\Redis::class)]
        private \Closure $redis,
    ) {
    }

    #[Route(path: '/health', name: 'health', methods: ['GET'])]
    public function __invoke(): Success
    {
        $failure = null;

        try {
            // Ответ-ошибку Redis (например, `-LOADING`, пока Redis загружает данные
            // с диска) phpredis может вернуть как `false`, не бросая исключения.
            if (true === ($this->redis)()->ping()) {
                return new Success('ok');
            }
        } catch (InvalidArgumentException|\RedisException $e) {
            // InvalidArgumentException бросает `RedisAdapter::createConnection`, когда
            // подключение не устанавливается, — вопреки своему docblock'у, где она обещана
            // только для невалидного DSN. Изменение этого поведения при обновлении
            // symfony/cache поймает набор `connect-fails` в features/service_health_readiness.feature.
            // \RedisException — отказ уже открытого соединения.
            $failure = $e;
        }

        throw new ServiceUnavailableHttpException(null, 'Storage is unavailable.', $failure);
    }
}
