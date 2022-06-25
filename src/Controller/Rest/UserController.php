<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Entity\Client;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OpenApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(path="/user")
 * @OpenApi\Tag(name="User")
 * @Nelmio\Security(name="Bearer")
 * @IsGranted("ROLE_API", statusCode=403, message="Access denied")
 */
class UserController extends AbstractFOSRestController
{
    /**
     * @Rest\Get("/me")
     * @OpenApi\Get(
     *     description="Current client information",
     *     @OpenApi\Response(
     *          response="200",
     *          description="Client info",
     *          @Nelmio\Model(type=Client::class)
     *     )
     * )
     */
    public function meAction(UserInterface $user): Client
    {
        /* @var Client $user */
        return $user;
    }
}
