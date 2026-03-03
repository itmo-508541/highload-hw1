<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Entity\Client;
use App\Model\Rest\Error;
use App\Traits\EntityManagerTrait;
use App\Traits\UserPasswordHasherTrait;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Attribute as Nelmio;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'User')]
#[Nelmio\Security(name: 'Bearer')]
#[Route(path: '/user')]
#[IsGranted('ROLE_API', message: 'Access denied', statusCode: 403)]
class UserController extends AbstractFOSRestController
{
    use EntityManagerTrait;
    use UserPasswordHasherTrait;

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

    #[Rest\Post('/change-password')]
    #[OA\Post(
        description: 'Update user password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password'],
                properties: [
                    new OA\Property(property: 'password', type: 'string', example: 'qwerty'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new Nelmio\Model(type: Client::class)),
            new OA\Response(response: 400, description: 'Invalid password', content: new Nelmio\Model(type: Error::class)),
        ]
    )]
    public function changePassword(Request $request, UserInterface $client): Client
    {
        /** @var Client $client */
        $plainPassword = $request->get('password', '');
        if ('' === (string) $plainPassword) {
            throw new BadRequestHttpException('Invalid password');
        }

        $password = $this->userPassworsHasher->hashPassword($client, $plainPassword);
        $client->setPassword($password);
        $this->em->persist($client);
        $this->em->flush();

        return $client;
    }
}
