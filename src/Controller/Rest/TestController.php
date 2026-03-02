<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Model\Test;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OpenApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @Nelmio\Security(name="Bearer")
 * @OpenApi\Tag(name="Test")
 */
#[Route(path: '/test', condition: "'dev' === '%kernel.environment%'")]
#[IsGranted('ROLE_API', message: 'Access denied', statusCode: 403)]
class TestController extends AbstractFOSRestController
{
    /**
     * @OpenApi\Get(
     *     description="Token and version testing",
     *     @OpenApi\Response(
     *         response="200",
     *         description="Test",
     *         @Nelmio\Model(type=Test::class)
     *     )
     * )
     */
    #[Rest\Get('/version', condition: "request.attributes.get('version') == 'v1.0'")]
    public function index(UserInterface $user): Test
    {
        return new Test(1, 2);
    }
}
