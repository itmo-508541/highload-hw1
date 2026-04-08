<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Entity\Client;
use App\Model\Rest\AuthToken;
use App\Model\Rest\Error;
use App\Traits\EntityManagerTrait;
use App\Traits\JWTManagerTrait;
use App\Traits\LoaderConfigTrait;
use App\Traits\MailerTrait;
use App\Traits\UserPasswordHasherTrait;
use App\Traits\ValidatorTrait;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Attribute as Nelmio;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/auth')]
#[OA\Tag(name: 'Auth')]
class AuthController extends AbstractFOSRestController
{
    use EntityManagerTrait;
    use JWTManagerTrait;
    use LoaderConfigTrait;
    use MailerTrait;
    use UserPasswordHasherTrait;
    use ValidatorTrait;

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
    public function createToken(Request $request): AuthToken
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        $repo = $this->em->getRepository(Client::class);
        $client = $repo->findOneBy(['username' => $username]);
        if (null === $client || !$this->userPassworsHasher->isPasswordValid($client, $password)) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        return new AuthToken($this->jwtManager->create($client));
    }
}
