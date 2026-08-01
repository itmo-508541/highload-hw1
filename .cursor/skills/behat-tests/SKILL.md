---
name: behat-tests
description: Описывает написание и обновление сценариев Behat (Gherkin) и контекстов в Symfony-проекте, запуск через Makefile в Docker. Использовать когда план или задача требует BDD/приёмочных сценариев, меняется поведение API/UI по сценариям, пользователь просит прогнать или добавить Behat, упоминаются `make behat`, `behat.yml.dist`, каталог `features/` или `App\Tests\Behat`.
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
- Сценарии: каталог `features/` (`*.feature`), контексты шагов: `tests/Behat/`, пространства имён вида `App\Tests\Behat\...`, регистрация контекстов в `behat.yml.dist` (или в `behat.yml`).
- Запуск **только в Docker**: контейнер `php` должен быть поднят (`make up`).
- Прямой `docker compose exec`: для неинтерактивного режима (скрипты, агенты, CI) используйте **`exec -T`** — без псевдо-TTY, как в цели `make behat` в `Makefile`.
- **Запрещено** сливать `openspec/specs/**` и `features/`: это разные слои
  (соглашение vs исполняемый BDD). Не переносить `.feature` в `openspec/` и
  не класть OpenSpec `spec.md` в `features/`.

## Рабочий цикл

1. Прочитать затронутые OpenSpec Scenario; решить, нужен ли исполняемый Behat
   (приёмка API/потока) или достаточно PHPUnit — см. `openspec/process.md`.
2. Уточнить по плану/tasks, какие сценарии или шаги нужны (новый `.feature`, доработка существующего, новые шаги в контексте).
3. Добавить или изменить Gherkin в `features/`, реализовать шаги в контексте в `tests/Behat/` — **сохранять стиль** соседних контекстов (атрибуты `#[When]` / `#[Then]` и т.д., как в `PhingDotEnvContext`).
4. При необходимости зарегистрировать новый класс контекста в `behat.yml.dist` (или в локальном `behat.yml`).
5. В `tasks.md` change указывать пути к `.feature` / контексту и связь со
   Scenario specs, где уместно (формальная матрица ID — вне scope; достаточно ссылок).
6. Запустить прогон:
   - весь набор: `make behat`
   - один feature: `make behat features/phing_dotenv_property_task.feature` или `make behat ARGS="features/phing_dotenv_property_task.feature"`
7. Исправить падения до зелёного прогона, если это в рамках задачи.

## Команды

```bash
make behat
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
