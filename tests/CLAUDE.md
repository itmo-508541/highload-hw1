# tests/

PHPUnit-тесты.

## Карта suite'ов

| Точка наблюдения эффекта | Каталог | Базовый класс | Что нужно снаружи | Прогон |
|---|---|---|---|---|
| возврат метода, исключение, вывод внешнего процесса | `tests/Unit/` | `TestCase` | ничего | `make test` |
| обязательный сеттер трейта | `tests/Unit/Traits/` | `RequiredSetterTestCase` | ничего | `make test` |
| сервис из контейнера, консольная команда ядра, сериализация | `tests/Integration/` | `KernelTestCase` | ядро | `make test-env` |
| сессия, записанная во внешнее хранилище | `tests/Integration/Session/` | `KernelTestCase` | ядро, Redis | `make test-env` |
| страница в браузере, поведение JS | `tests/E2E/` | `PantherTestCase` | браузер | `make test-env` |

Suite'ы в `phpunit.dist.xml`: `Unit`, `Integration`, `E2E` — по каталогам
первого уровня. Приложение под Panther поднимается в окружении `e2e`;
база у него та же, что у остальных suite'ов.

## Общие каталоги

Не suite'ы: PHPUnit их не обходит, тестов в них нет. Namespace —
`App\Tests\<Каталог>\…`. Код отсюда виден и контекстам Behat: `behat.yml.dist`
использует тот же `tests/bootstrap.php`, а `App\Tests\` автозагружается
из `tests/`.

| Каталог | Что лежит |
|---|---|
| — | заводятся по факту |

Сюда попадает то, что нужно **двум и более** тестам или обоим инструментам.
Фикстура одного теста остаётся в `./fixtures/` рядом с ним.

## Конвенции

- Namespace `App\Tests\<Suite>\…`: зеркальность `src/` отсчитывается от корня
  suite — `src/Manager/Foo.php` → `tests/Unit/Manager/FooTest.php` →
  `App\Tests\Unit\Manager\FooTest`.
- Фикстуры — в `./fixtures/` относительно `*Test.php`. Фикстура, нужная двум
  тестам из разных suite'ов, копируется, а не адресуется через `..`.
- Bootstrap — `tests/bootstrap.php` (Composer + Dotenv), `APP_ENV=test` задан
  в `phpunit.dist.xml`.
- `failOnDeprecation`, `failOnNotice`, `failOnWarning` включены: deprecation
  роняет прогон, глушить его в тесте нельзя.
- При наличии активного OpenSpec change — specs задают ожидаемое поведение.

## Команды

```bash
make test                                  # suite Unit
make test-env                              # suite'ы Integration и E2E
make test tests/Unit/Path/FooTest.php      # один файл, окружение не готовится
make test-env tests/E2E/Path/BarTest.php   # один файл, с подготовкой окружения
```

Путь к файлу отменяет фильтр по suite, поэтому цель выбирается по другому
признаку: `test-env` перед прогоном готовит dotenv через Phing, `test`
не делает ничего.

## Куда не класть

- Gherkin `.feature`, `*Context.php` → `features/`
- OpenSpec specs → `openspec/`
- Карточки механик проверки → `patterns/tests/`
