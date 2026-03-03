<?php

declare(strict_types=1);

namespace App\Controller\Rest;

use App\Entity\Client;
use App\Model\Rest\AuthToken;
use App\Model\Rest\Error;
use App\Model\Rest\Success;
use App\Repository\UserRepository;
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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationList;

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

    private const CONFIG_EMAIL = ['email'];

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
        $username = $request->get('username');
        $password = $request->get('password');

        $repo = $this->em->getRepository(Client::class);
        /* @var UserRepository $repo */
        $client = $repo->findOneBy(['username' => $username]);
        if (null === $client || !$this->userPassworsHasher->isPasswordValid($client, $password)) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        return new AuthToken($this->jwtManager->create($client));
    }

    #[Rest\Post('/create')]
    #[OA\Post(
        description: 'Register user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'example@gmail.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'qwerty'),
                    new OA\Property(property: 'firstname', type: 'string', example: 'John'),
                    new OA\Property(property: 'lastname', type: 'string', example: 'Doe'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new Nelmio\Model(type: AuthToken::class)),
            new OA\Response(response: 400, description: 'Bad request', content: new Nelmio\Model(type: Error::class)),
        ]
    )]
    public function create(Request $request): AuthToken
    {
        $username = $request->get('username');
        $password = $request->get('password');
        $firstname = $request->get('firstname', '');
        $lastname = $request->get('lastname', '');

        $token = $this->generateToken();

        $client = new Client();
        $client->setUsername($username);
        $client->setPassword($this->userPassworsHasher->hashPassword($client, $password));
        $client->setFirstname($firstname);
        $client->setLastname($lastname);
        $client->setConfirmationToken($token);
        $client->setConfirmedAt(null);

        $errors = $this->validator->validate($client);
        if ($errors instanceof ConstraintViolationList && \count($errors)) {
            throw new BadRequestHttpException($errors->get(0)->getMessage());
        }

        $this->em->persist($client);
        $this->em->flush();

        $email = (new TemplatedEmail())
            ->subject($this->loaderConfig->getValue(self::CONFIG_EMAIL, 'confirm.subject'))
            ->htmlTemplate('email/Client/confirmation.html.twig')
            ->context([
                'tokenUrl' => $this->loaderConfig->getTemplatedValue(self::CONFIG_EMAIL, 'forgot-password.url', [
                    'token' => $token,
                ]),
            ])
            ->addTo($username)
            ->addFrom($this->loaderConfig->getValue(self::CONFIG_EMAIL, 'from'));

        $this->mailer->send($email);

        return new AuthToken($this->jwtManager->create($client));
    }

    #[Rest\Post(path: '/confirm')]
    #[OA\Post(
        description: 'Confirm email',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'jsdbcbicb78wb'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new Nelmio\Model(type: AuthToken::class)),
            new OA\Response(response: 400, description: 'Bad request', content: new Nelmio\Model(type: Error::class)),
        ]
    )]
    public function confirm(Request $request): AuthToken
    {
        $token = $request->get('token');

        $repo = $this->em->getRepository(Client::class);
        $client = $repo->findOneBy([
            'confirmationToken' => $token,
        ]);
        /* @var Client|null $client */
        if (null === $client || null !== $client->getConfirmedAt()) {
            throw new BadRequestHttpException('Token was not found or already confirmed');
        }

        $client->setConfirmedAt(new \DateTime('now'));

        $this->em->persist($client);
        $this->em->flush();

        return new AuthToken($this->jwtManager->create($client));
    }

    #[Rest\Post(path: '/forgot-password')]
    #[OA\Post(
        description: 'Forgot password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'mail@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new Nelmio\Model(type: Success::class)),
        ]
    )]
    public function forgotPassword(Request $request): Success
    {
        $email = $request->get('email', '-');
        $client = $this->em->getRepository(Client::class)->findOneBy([
            'username' => $email,
        ]);
        if ($client instanceof Client) {
            $token = $this->generateToken();
            $client->setConfirmationToken($token);

            $this->em->persist($client);
            $this->em->flush();

            $email = (new TemplatedEmail())
                ->subject($this->loaderConfig->getValue(self::CONFIG_EMAIL, 'forgot-password.subject'))
                ->htmlTemplate('email/Client/forgot-password.html.twig')
                ->context([
                    'tokenUrl' => $this->loaderConfig->getTemplatedValue(self::CONFIG_EMAIL, 'forgot-password.url', [
                        'token' => $token,
                    ]),
                ])
                ->addTo($email)
                ->addFrom($this->loaderConfig->getValue(self::CONFIG_EMAIL, 'from'));

            $this->mailer->send($email);
        }

        return new Success('Ok');
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
