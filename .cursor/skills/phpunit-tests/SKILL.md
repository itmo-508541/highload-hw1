---
name: phpunit-tests
description: Описывает написание и обновление PHPUnit-тестов в Symfony-проекте и их запуск через Makefile в Docker. Использовать когда план или задача требует unit/integration-тестов, меняется код в `src/`, пользователь просит прогнать или добавить тесты, упоминаются `make test`, `phpunit.dist.xml` или каталог `tests/`.
---

# PHPUnit

## Контекст

- Конфигурация: `phpunit.dist.xml`, bootstrap: `tests/bootstrap.php`, окружение тестов: `APP_ENV=test` (задано в конфиге PHPUnit).
- Тесты: каталог `tests/`, пространства имён вида `App\Tests\...` для классов из `App\...`.
- Запуск **только в Docker**: контейнер `php` должен быть поднят (`make up`). На хосте без контейнера не полагаться на совпадение PHP/расширений с CI.

## Рабочий цикл

1. Определить класс(ы) в `src/`, которые нужно покрыть или обновить.
2. Найти существующий тест в `tests/` по зеркальной структуре (например `src/EventListener/Foo.php` → `tests/EventListener/FooTest.php`) или создать новый `*Test.php`.
3. Использовать `PHPUnit\Framework\TestCase` или интеграционные базовые классы Symfony из проекта, если они уже приняты в соседних тестах — **сохранять стиль существующих тестов** в том же каталоге.
4. Запустить прогон:
   - весь набор: `make test`
   - один файл: `make test tests/Path/To/SomeTest.php` или `make test ARGS="tests/Path/To/SomeTest.php"`
5. Исправить падения до зелёного прогона, если это в рамках задачи.

## Команды (эталон из README)

```bash
make test
make test tests/EventListener/CorsRequestListenerTest.php
make test ARGS="tests/EventListener/CorsRequestListenerTest.php"
```

Прямой вызов (если Makefile неудобен):

```bash
docker compose exec php vendor/bin/phpunit
docker compose exec php vendor/bin/phpunit tests/EventListener/CorsRequestListenerTest.php
```

## Мини-чеклист

- [ ] Тесты лежат в `tests/`, имена классов и файлов согласованы с принятыми в проекте примерами
- [ ] Выполнен `make test` (или узкий прогон изменённого файла) в запущенном контейнере `php`
- [ ] Пользователю при сдаче указаны пути к тестам и результат прогона

## Дополнительно

Подробные сценарии и переменные окружения для локальной разработки — в разделе «PHPUnit» файла `README.md`.
