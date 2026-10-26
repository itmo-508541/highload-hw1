<?php

declare(strict_types=1);

namespace App\Controller;

use App\Grafana\DashboardGenerator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/handlers', name: 'handlers', methods: ['GET'])]
final readonly class HandlersController
{
    public function __construct(
        private DashboardGenerator $dashboardGenerator,
        #[Autowire('%app.collect_metrics.names%')]
        private array $names,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $data = [];
        foreach ($this->names as $controller) {
            $data[] = [
                'uid' => $controller,
                'dashboard' => $this->dashboardGenerator->generate($controller),
            ];
        }

        return new JsonResponse($data);
    }
}
