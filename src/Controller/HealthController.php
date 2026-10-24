<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Rest\Success;
use Symfony\Component\Cache\Exception\InvalidArgumentException as RedisConnectionException;
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
        try {
            ($this->redis)()->ping();
        } catch (RedisConnectionException|\RedisException $e) {
            throw new ServiceUnavailableHttpException(null, 'Storage is unavailable.', $e);
        }

        return new Success('ok');
    }
}
