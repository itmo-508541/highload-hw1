<?php

declare(strict_types=1);

namespace App\Controller;

use OpenTelemetry\API\Metrics\MeterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/homepage')]
class HomepageController
{
    #[Route(path: '/')]
    public function indexAction(MeterInterface $meter): Response
    {
        $rand = random_int(0, 100);

        $latency = $meter->createHistogram('homepage_duration', 'ms');
        $latency->record($rand, ['status' => 'ok']);

        return new Response((string) $rand);
    }
}
