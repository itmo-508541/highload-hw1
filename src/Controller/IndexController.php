<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/')]
class IndexController
{
    public function __invoke(): Response
    {
        return new Response('');
    }
}
