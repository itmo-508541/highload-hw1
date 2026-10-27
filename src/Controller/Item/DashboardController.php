<?php

declare(strict_types=1);

namespace App\Controller\Item;

use App\Grafana\DashboardGenerator;
use Grafana\Foundation\Dashboard\Dashboard;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DashboardController
{
    public function __construct(
        private DashboardGenerator $dashboardGenerator,
        #[Autowire('%app.collect_metrics.names%')]
        private array $names,
    ) {
    }

    #[Route(path: '/items/dashboard', name: 'dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $dashboard = $this->dashboardGenerator->generate($this->names);

        return new JsonResponse($dashboard);
    }
}
