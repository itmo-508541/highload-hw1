<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Entity\Client;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Attribute as Nelmio;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'User')]
#[Nelmio\Security(name: 'Bearer')]
#[Route(path: '/user')]
#[IsGranted('ROLE_API', message: 'Access denied', statusCode: 403)]
class UserController extends AbstractFOSRestController
{
    #[Rest\Get('/me')]
    #[OA\Get(
        description: 'Current client information',
        responses: [
            new OA\Response(response: 200, description: 'Client info', content: new Nelmio\Model(type: Client::class)),
        ]
    )]
    public function me(UserInterface $client): Client
    {
        /** @var Client $client */
        return $client;
    }
}
