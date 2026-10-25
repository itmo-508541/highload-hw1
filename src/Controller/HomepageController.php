<?php

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\CollectMetrics;
use OpenTelemetry\API\Metrics\MeterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/homepage')]
class HomepageController
{
    #[IsGranted('PUBLIC_ACCESS')]
    #[CollectMetrics(name: 'user_homepage')]
    #[Route(path: '/')]
    public function __invoke(MeterInterface $meter): Response
    {
        if (random_int(0, 9) > 4) {
            throw new BadRequestHttpException();
        }

        return new Response('');
    }
}
