---
name: behat-tests
description: Описывает написание и обновление сценариев Behat (Gherkin) и контекстов в Symfony-проекте, запуск через Makefile в Docker. Использовать когда план или задача требует BDD/приёмочных сценариев, меняется поведение API/UI по сценариям, пользователь просит прогнать или добавить Behat, упоминаются `make behat`, `behat.yml.dist`, каталог `features/` или `App\Tests\Behat`.
---

# Behat

## Контекст

- Конфигурация по умолчанию: `behat.yml.dist`, bootstrap: `tests/bootstrap.php`, расширение Symfony: `FriendsOfBehat\SymfonyExtension` (ядро в окружении `test`).
- Локальные правки: копия `behat.yml.dist` → `behat.yml` (не коммитится).
- Сценарии: каталог `features/` (`*.feature`), контексты шагов: `tests/Behat/`, пространства имён вида `App\Tests\Behat\...`, регистрация контекстов в `behat.yml.dist` (или в `behat.yml`).
- Запуск **только в Docker**: контейнер `php` должен быть поднят (`make up`).

## Рабочий цикл

1. Уточнить по плану, какие сценарии или шаги нужны (новый `.feature`, доработка существующего, новые шаги в контексте).
2. Добавить или изменить Gherkin в `features/`, реализовать шаги в контексте в `tests/Behat/` — **сохранять стиль** соседних контекстов (атрибуты `#[When]` / `#[Then]` и т.д., как в `DemoContext`).
3. При необходимости зарегистрировать новый класс контекста в `behat.yml.dist` (или в локальном `behat.yml`).
4. Запустить прогон:
   - весь набор: `make behat`
   - один feature: `make behat features/demo.feature` или `make behat ARGS="features/demo.feature"`
5. Исправить падения до зелёного прогона, если это в рамках задачи.

## Команды

```bash
make behat
make behat features/demo.feature
make behat ARGS="features/demo.feature"
```

Прямой вызов (если Makefile неудобен):

```bash
docker compose exec php vendor/bin/behat
docker compose exec php vendor/bin/behat features/demo.feature
```

## Мини-чеклист

- [ ] Сценарии и шаги согласованы с принятыми в проекте примерами (`features/`, `tests/Behat/`)
- [ ] Контекст подключён в конфиге Behat при добавлении нового класса
- [ ] Выполнен `make behat` (или узкий прогон одного feature) в запущенном контейнере `php`
- [ ] Пользователю при сдаче указаны пути к feature/контексту и результат прогона
