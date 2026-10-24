<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Код ответа главной страницы — на настоящем объекте ответа ядра,
 * а не под браузером, где он не наблюдаем.
 */
final class IndexControllerTest extends WebTestCase
{
    public function testHomepageRespondsOk(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/');

        self::assertResponseStatusCodeSame(200);
    }
}
