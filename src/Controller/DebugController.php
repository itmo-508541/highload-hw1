<?php

declare(strict_types=1);

namespace App\Controller;

use App\Traits\EntityManagerTrait;
use App\Traits\SluggerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(condition: "'dev' === '%kernel.environment%'")]
class DebugController extends AbstractController
{
    use EntityManagerTrait;
    use SluggerTrait;

    #[Route(path: '/debug/zip')]
    public function index(): Response
    {
        return new Response($this->slugger->slug('test string')->toString());
    }
}
