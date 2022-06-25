<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Model\Test;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as Nelmio;
use OpenApi\Annotations as OpenApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(path="/api", condition="'dev' === '%kernel.environment%'")
 * @IsGranted("ROLE_API", statusCode=403, message="Access denied")
 * @Nelmio\Security(name="Bearer")
 * @OpenApi\Tag(name="Test")
 */
class TestController extends AbstractFOSRestController
{
    /**
     * @Rest\Get("/test", condition="request.attributes.get('version') == 'v1.0'")
     * @OpenApi\Get(
     *     description="Token testing",
     *     @OpenApi\Response(
     *         response="200",
     *         description="Test",
     *         @Nelmio\Model(type=Test::class)
     *     )
     * )
     */
    public function indexAction(Request $request, UserInterface $user): Test
    {
        return new Test(1, 2);
    }
}
