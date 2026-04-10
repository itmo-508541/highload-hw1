<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Model\Rest\AuthToken;
use App\Model\Rest\Error;
use App\Repository\ClientRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute as Nelmio;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/auth')]
#[OA\Tag(name: 'Auth')]
class AuthTokenController extends AbstractFOSRestController
{
    public function __construct(private readonly ClientRepository $repo, private readonly UserPasswordHasherInterface $userPasswordHasher, private readonly JWTTokenManagerInterface $jwtManager)
    {
    }

    #[Rest\Post(path: '/token')]
    #[OA\Post(
        description: 'JWT login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'example@gmail.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'qwerty'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new Nelmio\Model(type: AuthToken::class)),
            new OA\Response(response: 400, description: 'Bad request', content: new Nelmio\Model(type: Error::class)),
        ]
    )]
    public function createToken(
        Request $request,
    ): AuthToken {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        $client = $this->repo->findOneBy(['username' => $username]);
        if (null === $client || !$this->userPasswordHasher->isPasswordValid($client, $password)) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        return new AuthToken($this->jwtManager->create($client));
    }
}
