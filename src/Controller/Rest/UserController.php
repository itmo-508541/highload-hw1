<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Entity\Client;
use App\Model\Rest\Error;
use App\Traits\EntityManagerTrait;
use App\Traits\UserPasswordHasherTrait;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OpenApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @OpenApi\Tag(name="User")
 * @Nelmio\Security(name="Bearer")
 */
#[Route(path: '/user')]
#[IsGranted('ROLE_API', message: 'Access denied', statusCode: 403)]
class UserController extends AbstractFOSRestController
{
    use EntityManagerTrait;
    use UserPasswordHasherTrait;

    /**
     * @OpenApi\Get(
     *     description="Current client information",
     *     @OpenApi\Response(
     *          response="200",
     *          description="Client info",
     *          @Nelmio\Model(type=Client::class)
     *     )
     * )
     *
     * @param Client $client
     */
    #[Rest\Get('/me')]
    public function me(UserInterface $client): Client
    {
        return $client;
    }

    /**
     * @OpenApi\Post(
     *     description="Update user password",
     *     @OpenApi\RequestBody(
     *         required=true,
     *          @OpenApi\JsonContent(
     *              required={"password"},
     *              @OpenApi\Property(property="password", type="string", example="qwerty")
     *          )
     *     ),
     *     @OpenApi\Response(
     *          response="200",
     *          description="OK",
     *          @Nelmio\Model(type=Client::class)
     *     ),
     *     @OpenApi\Response(
     *          response="400",
     *          description="Invalid password",
     *          @Nelmio\Model(type=Error::class)
     *     )
     * )
     */
    #[Rest\Post('/change-password')]
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
