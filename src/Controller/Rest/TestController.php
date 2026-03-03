<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Model\Test;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Attribute as Nelmio;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Test')]
#[Nelmio\Security(name: 'Bearer')]
#[Route(path: '/test', condition: "'dev' === '%kernel.environment%'")]
#[IsGranted('ROLE_API', message: 'Access denied', statusCode: 403)]
class TestController extends AbstractFOSRestController
{
    #[Rest\Get('/version', condition: "request.attributes.get('version') == 'v1.0'")]
    #[OA\Get(
        description: 'Token and version testing',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Test',
                content: new Nelmio\Model(type: Test::class)
            ),
        ]
    )]
    public function index(UserInterface $user): Test
    {
        return new Test(1, 2);
    }
}
