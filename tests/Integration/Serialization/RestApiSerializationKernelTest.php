<?php

declare(strict_types=1);

namespace App\Tests\Integration\Serialization;

use App\Entity\Client;
use App\Entity\User;
use App\Model\Rest\AuthToken;
use App\Model\Rest\Error;
use App\Model\Rest\Success;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * JSON shape for REST API objects serialized with Symfony Serializer (group "api"), matching FOSRest + fos_rest.serializer.groups.
 */
#[CoversNothing]
final class RestApiSerializationKernelTest extends KernelTestCase
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->serializer = self::getContainer()->get('serializer');
    }

    public function testAuthTokenJsonShape(): void
    {
        $token = 'eyJ.test.signature';
        $authToken = new AuthToken($token);

        $data = $this->serializeToArray($authToken);

        self::assertSame(['token' => $token], $data);
    }

    public function testClientJsonShape(): void
    {
        $client = new Client();
        $this->setProtectedProperty($client, 'id', 7);
        $client->setUsername('client_user');
        $client->setPassword('hash');

        $data = $this->serializeToArray($client);

        self::assertSame([
            'id' => 7,
            'roles' => [
                'ROLE_USER',
                'ROLE_API',
            ],
            'username' => 'client_user',
        ], $data);
    }

    public function testUserJsonShape(): void
    {
        $user = new User();
        $this->setProtectedProperty($user, 'id', 99);
        $user->setUsername('admin');
        $user->setPassword('hash');

        $data = $this->serializeToArray($user);

        self::assertSame([
            'id' => 99,
            'username' => 'admin',
        ], $data);
    }

    public function testSuccessJsonShape(): void
    {
        $success = new Success('ok');

        $data = $this->serializeToArray($success);

        self::assertSame(['message' => 'ok'], $data);
    }

    public function testConcreteErrorJsonShape(): void
    {
        $error = new class extends Error {
        };
        $error->code = 'BAD_REQUEST';
        $error->message = 'Invalid input';

        $data = $this->serializeToArray($error);

        self::assertSame([
            'code' => 'BAD_REQUEST',
            'message' => 'Invalid input',
        ], $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeToArray(object $object): array
    {
        $json = $this->serializer->serialize($object, 'json', ['groups' => ['api']]);
        $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }
}
