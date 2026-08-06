---
name: behat-tests
description: >
  Пишет и обновляет Behat-сценарии (Gherkin в features/, контексты в
  tests/Behat/); прогон — make behat / make behat-e2e. Использовать при
  BDD/приёмочных сценариях, изменении API/UI-потоков, просьбе добавить или
  прогнать Behat, упоминании features/, features/e2e/, behat.yml.dist,
  make behat, make behat-e2e, App\Tests\Behat.
---

# Behat

## Контекст

- **Источник ожидаемого поведения:** OpenSpec — active change
  (`openspec/changes/<name>/specs/**`) и/или main specs (`openspec/specs/**`).
  Gherkin в `features/*.feature` **реализует или уточняет** OpenSpec Scenario
  для исполняемой приёмки; не копировать specs wholesale в `.feature` без нужды
  (избегайте дублирования ради дублирования).
- Конфигурация по умолчанию: `behat.yml.dist`, bootstrap: `tests/bootstrap.php`, расширение Symfony: `FriendsOfBehat\SymfonyExtension` (ядро в окружении `test`).
- Локальные правки: копия `behat.yml.dist` → `behat.yml` (не коммитится).
- Сценарии: «быстрая» приёмка — `features/` (suite `default`, filter `~@e2e`); E2E — `features/e2e/` (suite `e2e`, Mink+Panther). Контексты: `tests/Behat/`, регистрация в `behat.yml.dist`.
- Запуск **только в Docker**: контейнер `php` должен быть поднят (`make up`).
- Прямой `docker compose exec`: для неинтерактивного режима (скрипты, агенты, CI) используйте **`exec -T`** — без псевдо-TTY, как в цели `make behat` в `Makefile`.
- **Запрещено** сливать `openspec/specs/**` и `features/`: это разные слои
  (соглашение vs исполняемый BDD). Не переносить `.feature` в `openspec/` и
  не класть OpenSpec `spec.md` в `features/`. E2E не смешивать с suite `default`.

## Рабочий цикл

1. Прочитать затронутые OpenSpec Scenario; решить, нужен ли исполняемый Behat
   (приёмка API/потока) или достаточно PHPUnit — см. `openspec/process.md`.
2. Уточнить по плану/tasks, какие сценарии или шаги нужны (новый `.feature`, доработка существующего, новые шаги в контексте).
3. Добавить или изменить Gherkin в `features/`, реализовать шаги в контексте в `tests/Behat/` — **сохранять стиль** соседних контекстов (атрибуты `#[When]` / `#[Then]` и т.д., как в `PhingDotEnvContext`).
4. При необходимости зарегистрировать новый класс контекста в `behat.yml.dist` (или в локальном `behat.yml`).
5. В `tasks.md` change указывать пути к `.feature` / контексту и связь со
   Scenario specs, где уместно (формальная матрица ID — вне scope; достаточно ссылок).
6. Запустить прогон:
   - default: `make behat`
   - E2E: `make behat-e2e` (нужен Redis; см. `docs/e2e.md`)
   - один feature: `make behat features/phing_dotenv_property_task.feature` или `make behat ARGS="features/phing_dotenv_property_task.feature"`
7. Исправить падения до зелёного прогона, если это в рамках задачи.

## Команды

```bash
make behat
make behat-e2e
make behat features/phing_dotenv_property_task.feature
make behat ARGS="features/phing_dotenv_property_task.feature"
```

Прямой вызов (если Makefile неудобен):

```bash
docker compose exec -T php vendor/bin/behat --no-interaction
docker compose exec -T php vendor/bin/behat --no-interaction features/phing_dotenv_property_task.feature
```

## Мини-чеклист

- [ ] Прочитаны затронутые OpenSpec specs/change; `.feature` не дублирует wholesale
- [ ] Сценарии и шаги согласованы с принятыми в проекте примерами (`features/`, `tests/Behat/`)
- [ ] Каталоги `openspec/specs` и `features` не смешивались
- [ ] Контекст подключён в конфиге Behat при добавлении нового класса
- [ ] Выполнен `make behat` (или узкий прогон одного feature) в запущенном контейнере `php`
- [ ] Пользователю при сдаче указаны пути к feature/контексту и результат прогона
