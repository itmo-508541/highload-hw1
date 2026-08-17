## Why

Тип: **чистый refactor/tooling**.

Контексты Behat живут в `tests/Behat/` (`App\Tests\Behat`), а фикстуры Phing и
generate-secret — в общей свалке `tests/tools/`. Для ИИ-агента это исключение из
правила «`tests/` — PHPUnit, `features/` — Behat»: скиллы и AGENTS вынуждены
объяснять, что `*Context.php` можно класть под PHPUnit. Нужно выровнять раскладку
с дефолтом Behat (`features/bootstrap/`) и закрыть исключение в правилах.

## What Changes

- Перенести контексты в `features/bootstrap/` с namespace `App\Behat\`.
- Разнести фикстуры из `tests/tools/` к владельцу: `./fixtures/` рядом с
  `*Context.php` или `*Test.php`.
- В Gherkin шаг Phing указывает путь относительно каталога контекстов
  (`fixtures/tools/<dir>` → `features/bootstrap/fixtures/tools/<dir>`).
- `tests/bootstrap.php` остаётся на месте: это **test env autoload** (Composer +
  Dotenv), его же читает FoB в `behat.yml.dist`; не каталог контекстов Behat.
- PHPStan и Rector проверяют `features/bootstrap/` (фикстуры Rector не
  трансформирует).
- Обновить скиллы `behat-tests` / `phpunit-tests`, AGENTS и `openspec/process.md`,
  чтобы правило было без оговорок: `features/` — только Behat, `tests/` — только
  PHPUnit.

## Non-goals

- Менять смысл сценариев PHPUnit/Behat или выкидывать FriendsOfBehat.
- Переносить или переименовывать `tests/bootstrap.php`.
- Сливать `openspec/specs` с `features/`.
- Отдельный browser/E2E-контур — без изменений.

## Capabilities

### New Capabilities

_(нет)_

### Modified Capabilities

- `qa`: требование «Раздельные каталоги» явно фиксирует владельцев слоёв —
  контексты Behat только в `features/bootstrap/` (`App\Behat\`), PHPUnit только
  в `tests/`, фикстуры только рядом с владельцем в `./fixtures/`.

## Impact

- **Код / конфиг:** `behat.yml.dist` (FQCN контекстов; `bootstrap: tests/bootstrap.php`
  без изменений), `config/services_test.yaml`, `composer.json` autoload-dev
  (`App\\Behat\\` → `features/bootstrap/`), `phpstan.dist.neon`, `rector.php`.
- **Перенос:** `tests/Behat/*Context.php` → `features/bootstrap/`; фикстуры
  Phing → `features/bootstrap/fixtures/tools/`; generate-secret →
  `tests/Command/fixtures/tools/`.
- **Тесты:** правки путей в `PhingDotEnvContext`, `features/phing_dotenv_property_task.feature`,
  `GenerateSecretCommand*Test.php`. Поведение сценариев то же.
- **Документы / процесс:** `openspec/process.md`, `features/AGENTS.md`,
  `tests/AGENTS.md`, skills `behat-tests` и `phpunit-tests`.
- **API:** без изменений контрактов REST/Admin.
