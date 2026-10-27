<?php

declare(strict_types=1);

namespace App\Grafana;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RandomError
{
    public function __construct(
        #[Autowire(param: 'kernel.environment')]
        private readonly string $env,
    ) {
    }

    public function run(): void
    {
        if ('prod' !== $this->env) {
            return;
        }

        $rand = random_int(0, 100);
        if ($rand <= 3) {
            $statusCode = 400;
        } elseif ($rand <= 6) {
            $statusCode = 403;
        } elseif ($rand <= 9) {
            $statusCode = 406;
        } else {
            $statusCode = null;
        }

        if (null !== $statusCode) {
            throw HttpException::fromStatusCode($statusCode);
        }
    }
}
