<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

/**
 * Под браузером кода ответа нет: `getInternalResponse()` у Panther всегда
 * отдаёт 200. Код ответа проверяет `App\Tests\Integration\Controller\IndexControllerTest`,
 * здесь — только то, что видно в окне: адрес и содержимое страницы.
 */
final class HomepageTest extends PantherTestCase
{
    public function testVisit(): void
    {
        $client = self::createPantherClient([
            'env' => ['APP_ENV' => 'e2e'],
        ]);
        $crawler = $client->request('GET', '/');

        self::assertStringEndsWith('/', $client->getCurrentURL());
        // Главная отдаёт пустое тело; страница ошибки Symfony непуста.
        self::assertSame('', $crawler->filter('body')->text(), 'Вместо главной страницы браузер показал другую страницу.');
    }
}
